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

namespace AudiencePlayer\AudiencePlayerApiClient;

use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationMutation;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationQuery;

class AudiencePlayerApiClient
{
    public const
        GRAPHQL_OPERATION_SORT_DIRECTION_ASC = Globals::GRAPHQL_OPERATION_SORT_DIRECTION_ASC,
        GRAPHQL_OPERATION_SORT_DIRECTION_DESC = Globals::GRAPHQL_OPERATION_SORT_DIRECTION_DESC,

        OAUTH_SCOPE_USER = Globals::OAUTH_SCOPE_USER,
        OAUTH_SCOPE_ADMIN = Globals::OAUTH_SCOPE_ADMIN;

    private
        $graphQLService;

    protected static
        $instance;

    public
        $query,
        $mutation;

    /**
     * AudiencePlayerApiClient constructor.
     * @param GraphQLService $graphQLService
     * @param GraphQLOperationMutation $graphQLOperationMutation
     * @param GraphQLOperationQuery $graphQLOperationQuery
     */
    public function __construct(
        GraphQLService $graphQLService,
        GraphQLOperationMutation $graphQLOperationMutation,
        GraphQLOperationQuery $graphQLOperationQuery
    )
    {
        $this->graphQLService = $graphQLService;
        $this->mutation = $graphQLOperationMutation;
        $this->query = $graphQLOperationQuery;
    }

    /**
     * @param string $oauthClientId
     * @param string $oauthClientSecret
     * @param int $projectId
     * @param string $apiBaseUrl
     * @param string $locale
     * @return AudiencePlayerApiClient
     * @throws Exceptions\CustomException
     */
    public static function init(
        string $oauthClientId,
        string $oauthClientSecret,
        int $projectId,
        string $apiBaseUrl,
        string $locale = 'en'
    )
    {
        if (!self::$instance) {

            $graphQLService = new GraphQLService();

            self::$instance = new AudiencePlayerApiClient(
                $graphQLService,
                new GraphQLOperationMutation($graphQLService),
                new GraphQLOperationQuery($graphQLService)
            );
        }

        self::$instance->hydrateConfig($oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl);
        self::$instance->hydrateLocale($locale);

        return self::$instance;
    }

    /**
     * @param string $oauthClientId
     * @param string $oauthClientSecret
     * @param int $projectId
     * @param string $apiBaseUrl
     * @throws Exceptions\CustomException
     */
    public function hydrateConfig(string $oauthClientId, string $oauthClientSecret, int $projectId, string $apiBaseUrl)
    {
        $this->graphQLService->setOAuthClient($oauthClientId, $oauthClientSecret);
        $this->graphQLService->setApiBaseUrl($apiBaseUrl);
        $this->graphQLService->setProjectId($projectId);
    }

    /**
     * @param string $locale
     * @return string
     */
    public function hydrateLocale(string $locale)
    {
        return $this->graphQLService->setLocale($locale);
    }

    /**
     * @param string $bearerToken
     * @param int $minimumTtl
     * @param bool $isAllowRenewal
     * @return bool|string
     */
    public function hydrateValidatedOrRenewedBearerTokenForClient(string $bearerToken = '', int $minimumTtl = 60, bool $isAllowRenewal = true)
    {
        $ret = false;

        if ($bearerToken && $this->graphQLService->validateBearerTokenExpiry($bearerToken, Globals::OAUTH_SCOPE_ADMIN, $minimumTtl)) {

            $ret = $this->graphQLService->setBearerToken($bearerToken, Globals::OAUTH_ACCESS_AS_AGENT_CLIENT);

        } elseif ($isAllowRenewal) {

            $result = $this->mutation->AdminClientAuthenticate(
                $this->graphQLService->fetchOAuthClientId(),
                $this->graphQLService->fetchOAuthClientSecret(),
                $this->graphQLService->fetchProjectId()
            )->execute();

            if (isset($result->getData(true)->access_token)) {
                $ret = $this->graphQLService->setBearerToken($result->getData(true)->access_token, Globals::OAUTH_ACCESS_AS_AGENT_CLIENT);
            }
        }

        return $ret;
    }

    /**
     * @param int $userId
     * @param string $userEmail
     * @param array $userArgs
     * @param bool $isAutoRegister
     * @param string $bearerToken
     * @param int $minimumTtl
     * @param bool $isAllowRenewal
     * @return array<mixed>
     */
    public function hydrateValidatedOrRenewedBearerTokenForUser(
        int $userId,
        string $userEmail,
        array $userArgs = [],
        string $bearerToken = '',
        bool $isAutoRegister = true,
        bool $isAllowRenewal = true,
        int $minimumTtl = 60
    ): array
    {
        $ret = [
            'user_id' => null,
            'user_email' => null,
            'access_token' => null,
            'expires_in' => null,
        ];
        $args = [];

        if ($bearerToken && $this->graphQLService->validateBearerTokenExpiry($bearerToken, Globals::OAUTH_SCOPE_USER, $minimumTtl)) {

            $ret['access_token'] = $this->graphQLService->setBearerToken($bearerToken, Globals::OAUTH_ACCESS_AS_AGENT_USER);

        } elseif ($isAllowRenewal) {

            // Try to authenticate user by id (auto-creation explicitly is excluded)
            if ($userId) {

                $result = $this->mutation->ClientUserAuthenticateById(
                    $this->graphQLService->fetchOAuthClientId(),
                    $this->graphQLService->fetchOAuthClientSecret(),
                    $userId,
                    false
                )
                    ->arguments(trim($userEmail) ? ['user_email' => $userEmail] : [])
                    ->execute();

                if (isset($result->getData(true)->access_token)) {
                    $ret = array_merge($ret, (array)$result->getData(true));
                    $ret['access_token'] = $this->graphQLService->setBearerToken($result->getData(true)->access_token, Globals::OAUTH_ACCESS_AS_AGENT_USER);
                } elseif ($result->getFirstErrorCode() !== 404) {
                    // There was an error other than "user not found", ensure script flow is terminated
                    $userEmail = null;
                    return $ret;
                }
            }

            // If user was not found by id (e.g. because it was deleted), try to authenticate (or auto-create) by e-mail
            if (!$ret['access_token'] && $userEmail) {

                // Assemble user arguments for mutation
                if ($userArgs['user_password'] ?? $userArgs['password'] ?? null) {
                    $args['user_password'] = $userArgs['user_password'] ?? $userArgs['password'];
                }
                if ($userArgs['user_locale'] ?? $userArgs['locale'] ?? null) {
                    $args['user_locale'] = $userArgs['user_locale'] ?? $userArgs['locale'];
                }
                if ($userArgs['user_name'] ?? $userArgs['name'] ?? null) {
                    $args['user_name'] = $userArgs['user_name'] ?? $userArgs['name'];
                }

                // additional userArgs can be passed for auto-creation: $userArgs = ['user_password' => '****', 'locale' => 'en']
                $result = $this->mutation->ClientUserAuthenticateByEmail(
                    $this->graphQLService->fetchOAuthClientId(),
                    $this->graphQLService->fetchOAuthClientSecret(),
                    $userEmail,
                    $isAutoRegister
                )
                    ->arguments($args)
                    ->execute();

                if (isset($result->getData(true)->access_token)) {
                    $ret = array_merge($ret, (array)$result->getData(true));
                    $ret['access_token'] = $this->graphQLService->setBearerToken($result->getData(true)->access_token, Globals::OAUTH_ACCESS_AS_AGENT_USER);
                }
            }
        }

        return $ret;
    }

    /**
     * @param int $userId
     * @param string $userEmail
     * @param array $responseProperties
     * @return Resources\ApiResponse|bool
     */
    public function fetchUser(int $userId, string $userEmail = '', array $responseProperties = [])
    {
        $result = $this->query->ClientUser(
            $this->graphQLService->fetchOAuthClientId(),
            $this->graphQLService->fetchOAuthClientSecret(),
            $userId,
            $userEmail
        )
            ->properties($responseProperties ?: ['id', 'email'])
            ->execute();

        return $responseProperties ? $result : $result->isSuccessful();
    }

    /**
     * @param string $accessAgentType
     * @return string
     */
    public function fetchBearerToken(string $accessAgentType): string
    {
        return $this->graphQLService->fetchBearerToken($accessAgentType);
    }

    /**
     * @param int $userId
     * @param array $updateArguments
     * @param array $responseProperties
     * @return Resources\ApiResponse|bool
     */
    public function updateUser(int $userId, array $updateArguments, array $responseProperties = [])
    {
        $result = $this->mutation->ClientUserUpdate(
            $this->graphQLService->fetchOAuthClientId(),
            $this->graphQLService->fetchOAuthClientSecret(),
            $userId
        )
            ->arguments($updateArguments)
            ->properties($responseProperties ?: ['id', 'email'])
            ->execute();

        return $responseProperties ? $result : $result->isSuccessful();
    }

    /**
     * @param int $userId
     * @param string $userEmail
     * @return bool
     */
    public function deleteUser(int $userId, string $userEmail): bool
    {
        return $this->mutation->ClientUserDelete(
            $this->graphQLService->fetchOAuthClientId(),
            $this->graphQLService->fetchOAuthClientSecret(),
            $userId,
            $userEmail
        )
            ->execute()
            ->isSuccessful();
    }

    /**
     * @param bool $isErrorsOnly
     * @param bool $isStringifyResult
     * @return mixed|string
     */
    public function fetchLastOperationResult($isErrorsOnly = false, $isStringifyResult = false)
    {
        $lastOperation = $this->graphQLService->fetchLastOperationResult();

        if ($isErrorsOnly) {
            $lastOperation = is_string($lastOperation) ? json_decode($lastOperation) : $lastOperation;
            $lastOperation = $isErrorsOnly ? ($lastOperation['errors'] ?? []) : $lastOperation;
        }

        return $isStringifyResult && !is_string($lastOperation) ? json_encode($lastOperation) : $lastOperation;
    }

    /**
     * @codeCoverageIgnore
     * @param string $scope
     * @param string $operation
     * @param array $variables
     * @param bool $isExecuteAsPostRequest
     * @param bool $isResponseAsObject
     * @param string $operationName
     * @param string $bearerToken
     * @param string $accessType
     * @return false|mixed|object|string
     */
    public function executeRawGraphQLCall(
        string $scope,
        string $operation,
        array $variables = [],
        bool $isExecuteAsPostRequest = true,
        bool $isResponseAsObject = true,
        string $operationName = '',
        string $bearerToken = '',
        string $accessType = ''
    )
    {
        if (empty($bearerToken) && in_array($accessType, Globals::OAUTH_ACCESS_AGENTS)) {
            $bearerToken = $this->graphQLService->fetchBearerToken($accessType);
        }

        return $this->graphQLService->dispatchGraphQLCall(
            $scope,
            $operation,
            $variables,
            $isExecuteAsPostRequest,
            $isResponseAsObject,
            $operationName,
            $bearerToken
        );
    }

}
