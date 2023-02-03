<?php
/**
 * Copyright (c) 2020, AudiencePlayer
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @license     Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @author      AudiencePlayer <support@audienceplayer.com>
 * @copyright   AudiencePlayer
 * @link        https://www.audienceplayer.com
 */

declare(strict_types=1);

namespace AudiencePlayer\AudiencePlayerApiClient\Services;

use AudiencePlayer\AudiencePlayerApiClient\Exceptions\CustomException;
use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Helper;

class GraphQLService
{
    private $helper;

    protected
        $oauthClientId,
        $oauthClientSecret,
        $projectId,
        $apiBaseUrl,
        $bearerTokens,
        $locale,
        $ipAddress,
        $isExecuteAsPostRequest,
        $lastOperationQuery,
        $lastOperationVariables,
        $lastOperationResult;

    /**
     * GraphQLService constructor.
     * @param Helper|null $helper
     */
    public function __construct(Helper $helper = null)
    {
        $this->helper = $helper ?: new Helper();

        $this->bearerTokens = [
            Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '',
            Globals::OAUTH_ACCESS_AS_AGENT_USER => '',
        ];

        $this->setIsExecuteAsPostRequest();
    }

    /**
     * @param string $accessAgentType
     * @param string $scope
     * @param string $operationType
     * @param string $operationName
     * @param array $args
     * @param array $responseProperties
     * @return ApiResponse
     */
    public function assembleAndDispatchGraphQLCall(
        string $accessAgentType,
        string $scope,
        string $operationType,
        string $operationName,
        array $args,
        array $responseProperties
    ): ApiResponse
    {
        if (
            in_array($operationType, [Globals::GRAPHQL_OPERATION_TYPE_MUTATION, Globals::GRAPHQL_OPERATION_TYPE_QUERY]) &&
            ($query = $this->assembleGraphQLQueryString($operationType, $operationName, $args, $responseProperties))
        ) {

            $ret = $this->dispatchGraphQLCall(
                $scope,
                $query,
                [],
                $this->fetchIsExecuteAsPostRequest($operationType),
                true,
                $operationName,
                $this->fetchBearerToken($accessAgentType)
            );

        } else {

            $ret = ApiResponse::assembleDefaultErrorResponse(
                $operationName,
                Globals::MSG_ARGUMENT_ERROR,
                Globals::STATUS_ARGUMENT_ERROR
            );
        }

        return new ApiResponse($ret, $operationName, $this->fetchLastOperationQuery(), $this->fetchLastOperationVariables());
    }

    /**
     * @param string $scope
     * @param string $query
     * @param array $variables
     * @param bool $isExecuteAsPostRequest
     * @param bool $isResponseAsObject
     * @param string $operationName
     * @param string $bearerToken
     * @return false|mixed|object|string
     */
    public function dispatchGraphQLCall(
        string $scope,
        string $query,
        array $variables = [],
        bool $isExecuteAsPostRequest = true,
        bool $isResponseAsObject = true,
        string $operationName = '',
        string $bearerToken = ''
    )
    {
        if ($apiUrl = $this->assembleApiUrl($scope)) {

            // prepare curl request
            $options = [
                'CURLOPT_HTTPHEADER' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
            ];

            if ($this->locale) {
                array_push($options['CURLOPT_HTTPHEADER'], 'Accept-Language: ' . $this->locale);
            }

            if ($bearerToken) {
                array_push($options['CURLOPT_HTTPHEADER'], 'Authorization: Bearer ' . $bearerToken);
            }

            if ($isExecuteAsPostRequest) {

                $options['CURLOPT_POSTFIELDS']['query'] = $query;

                //if ($operationName) {
                //    $options['CURLOPT_POSTFIELDS']['operationName'] = $operationName;
                //}

                if ($variables) {
                    $options['CURLOPT_POSTFIELDS']['variables'] = $variables;
                }

                $options['CURLOPT_POSTFIELDS'] = json_encode($options['CURLOPT_POSTFIELDS']);

            } else {

                $apiUrl .= $variables ?
                    '?query=' . json_encode($query) . '&variables=' . json_encode($variables) :
                    '?query=' . json_encode($query);
            }

            // execute curl request
            try {

                $this->setLastOperation($query, $variables, $ret = $isResponseAsObject ?
                    json_decode($this->helper->dispatchCurlCall($apiUrl, $options, $this->fetchIpAddress())) :
                    $this->helper->dispatchCurlCall($apiUrl, $options, $this->fetchIpAddress())
                );

            } catch (\Exception $e) {

                $ret = ApiResponse::assembleDefaultErrorResponse(
                    $operationName,
                    Globals::MSG_API_DISPATCH_EXCEPTION,
                    Globals::STATUS_API_DISPATCH_EXCEPTION
                );

                $ret = $isResponseAsObject ? $ret : json_encode($ret);
            }

        } else {

            $ret = ApiResponse::assembleDefaultErrorResponse(
                $operationName,
                Globals::MSG_CONFIG_ERROR,
                Globals::STATUS_CONFIG_ERROR
            );
        }

        return $ret;
    }

    /**
     * @param string $bearerToken
     * @param string $tokenComponent
     * @return mixed|null
     */
    public function parseBearerToken(string $bearerToken, string $tokenComponent = Globals::BEARER_TOKEN_COMPONENT_PAYLOAD)
    {
        if ($tokenComponents = explode('.', $bearerToken)) {

            if ($tokenComponent === Globals::BEARER_TOKEN_COMPONENT_HEADER) {
                $index = 0;
            } elseif ($tokenComponent === Globals::BEARER_TOKEN_COMPONENT_PAYLOAD) {
                $index = 1;
            } else {
                $index = 2;
            }

            if (isset($tokenComponents[$index]) && ($data = json_decode(base64_decode($tokenComponents[$index])))) {
                return $data;
            }
        }

        return null;
    }

    /**
     * @param string $bearerToken
     * @param string $scope
     * @param int $minimumTtl
     * @return bool
     */
    public function validateBearerTokenExpiry(string $bearerToken, string $scope = '', int $minimumTtl = 60): bool
    {
        if (
            ($data = $this->parseBearerToken($bearerToken)) &&
            isset($data->exp)
        ) {
            return ($data->scopes ?? []) && in_array($scope, Globals::OAUTH_SCOPES) ?
                $data->exp > (time() + $minimumTtl) && in_array($scope, $data->scopes) :
                $data->exp > (time() + $minimumTtl);
        } else {
            return false;
        }
    }

    /**
     * @param bool $isMutationAsPostRequest
     * @param bool $isQueryAsPostRequest
     * @return void
     */
    public function setIsExecuteAsPostRequest(bool $isMutationAsPostRequest = true, bool $isQueryAsPostRequest = true): void
    {
        $this->isExecuteAsPostRequest = [
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION => $isMutationAsPostRequest,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY => $isQueryAsPostRequest,
        ];
    }

    public function fetchIsExecuteAsPostRequest($operationType): bool
    {
        return boolval($this->isExecuteAsPostRequest[$operationType] ?? true);
    }

    /**
     * @param string $bearerToken
     * @param string $accessAgentType
     * @return string
     */
    public function setBearerToken(string $bearerToken, string $accessAgentType): string
    {
        if (in_array($accessAgentType, [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT, Globals::OAUTH_ACCESS_AS_AGENT_USER])) {

            return $this->bearerTokens[$accessAgentType] = $bearerToken;

        } else {

            return '';
        }
    }

    /**
     * @param string $accessAgentType
     * @return string
     */
    public function fetchBearerToken(string $accessAgentType = Globals::OAUTH_ACCESS_AS_AGENT_CLIENT): string
    {
        return $this->bearerTokens[$accessAgentType] ?? '';
    }

    /**
     * @param string $oauthClientId
     * @param string $oauthClientSecret
     * @return void
     * @throws CustomException
     */
    public function setOAuthClient(string $oauthClientId, string $oauthClientSecret): void
    {
        if ($oauthClientId) {
            $this->oauthClientId = $oauthClientId;
        } else {
            throw new CustomException(
                Globals::MSG_CONFIG_CLIENT_ID_ERROR,
                Globals::STATUS_CONFIG_CLIENT_ID_ERROR
            );
        }

        if ($oauthClientSecret) {
            $this->oauthClientSecret = $oauthClientSecret;
        } else {
            throw new CustomException(
                Globals::MSG_CONFIG_CLIENT_SECRET_ERROR,
                Globals::STATUS_CONFIG_CLIENT_SECRET_ERROR
            );
        }
    }

    /**
     * @return string
     */
    public function fetchOAuthClientId(): string
    {
        return strval($this->oauthClientId);
    }

    /**
     * @return string
     */
    public function fetchOAuthClientSecret(): string
    {
        return strval($this->oauthClientSecret);
    }

    /**
     * @param int $projectId
     * @return int
     * @throws CustomException
     */
    public function setProjectId(int $projectId): int
    {
        if ($projectId > 0) {
            return $this->projectId = $projectId;
        } else {
            throw new CustomException(
                Globals::MSG_CONFIG_PROJECT_ID_ERROR,
                Globals::STATUS_CONFIG_PROJECT_ID_ERROR
            );
        }
    }

    /**
     * @return int
     */
    public function fetchProjectId(): int
    {
        return intval($this->projectId);
    }

    /**
     * @param string $apiBaseUrl
     * @return string
     * @throws CustomException
     */
    public function setApiBaseUrl(string $apiBaseUrl): string
    {
        $apiBaseUrl = rtrim($apiBaseUrl, '/');

        if (filter_var($apiBaseUrl, FILTER_VALIDATE_URL)) {
            return $this->apiBaseUrl = $apiBaseUrl;
        } else {
            throw new CustomException(
                Globals::MSG_CONFIG_API_URL_ERROR,
                Globals::STATUS_CONFIG_API_URL_ERROR
            );
        }
    }

    /**
     * @return string
     */
    public function fetchApiBaseUrl(): string
    {
        return strval($this->apiBaseUrl);
    }

    /**
     * @param string $locale
     * @return string
     */
    public function setLocale(string $locale): string
    {
        return $this->locale = trim($locale);
    }

    /**
     * @return string
     */
    public function fetchLocale(): string
    {
        return strval($this->locale);
    }

    /**
     * @param string $ipAddress
     * @return string
     */
    public function setIpAddress(string $ipAddress): string
    {
        return $this->ipAddress = trim($ipAddress);
    }

    /**
     * @return string
     */
    public function fetchIpAddress(): string
    {
        return strval($this->ipAddress);
    }

    /**
     * @param string $operation
     * @param array $variables
     * @param $result
     * @return void
     */
    public function setLastOperation(string $operation, array $variables, $result): void
    {
        $this->lastOperationQuery = $operation;
        $this->lastOperationVariables = $variables;
        $this->lastOperationResult = $result;
    }

    /**
     * @return string
     */
    public function fetchLastOperationQuery(): string
    {
        return strval($this->lastOperationQuery);
    }

    /**
     * @return array
     */
    public function fetchLastOperationVariables(): array
    {
        return (array)$this->lastOperationVariables ?: [];
    }

    /**
     * @return array
     */
    public function fetchLastOperationResult(): array
    {
        return (array)$this->lastOperationResult ?: [];
    }

    // ### PROTECTED HELPER METHODS

    /**
     * @param array $args
     * @param bool $isWithParentheses
     * @return string
     */
    protected function parseGraphQLArgsFromArray(array $args, bool $isWithParentheses = true): string
    {
        array_walk($args, function (&$value, $key) {
            $value = $this->helper->parseSingleGraphQLArgument($value, $key);
        });

        if ($args) {

            return $isWithParentheses ? '(' . implode(',', $args) . ')' : implode(',', $args);

        } else {

            return '';
        }
    }

    /**
     * @param array $args
     * @param bool $isWithBraces
     * @return string
     */
    protected function parseGraphQLPropsFromArray(array $args, bool $isWithBraces = true): string
    {
        array_walk($args, function (&$value, $key) {

            if (is_array($value)) {
                $value = array_key_exists('value', $value) ? $value['value'] : implode(',', $value);
            }

            if (false === is_numeric($key)) {
                $value = $key . '{' . $value . '}';
            }
        });

        if ($args) {

            return $isWithBraces ? '{' . implode(',', $args) . '}' : implode(',', $args);

        } else {

            return '';
        }
    }

    /**
     * @param string $operationType
     * @param string $operationName
     * @param array $args
     * @param array $responseProperties
     * @return string
     */
    protected function assembleGraphQLQueryString(
        string $operationType,
        string $operationName,
        array $args,
        array $responseProperties
    ): string
    {
        return
            $operationType . '{' .
            $operationName .
            $this->parseGraphQLArgsFromArray($args, true) .
            ($responseProperties ? '{' . $this->parseGraphQLPropsFromArray($responseProperties, false) . '}' : '') .
            '}';
    }

    /**
     * @param string $scope
     * @return null|string
     */
    protected function assembleApiUrl(string $scope): ?string
    {
        if ($this->fetchApiBaseUrl()) {

            if ($scope === Globals::OAUTH_SCOPE_ADMIN) {

                return $this->fetchApiBaseUrl() . '/graphql/' . $this->fetchProjectId() . '/admin';

            } elseif ($scope === Globals::OAUTH_SCOPE_USER && $this->fetchProjectId()) {

                return $this->fetchApiBaseUrl() . '/graphql/' . $this->fetchProjectId() . '/user';
            }
        }

        return null;
    }

}
