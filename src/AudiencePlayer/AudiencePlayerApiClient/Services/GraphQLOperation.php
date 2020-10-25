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
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
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

use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Helper;

class GraphQLOperation
{
    private const
        PARAMETER_TYPE_ARGUMENT = 'argument',
        PARAMETER_TYPE_PROPERTY = 'property';

    protected $graphQLService;
    protected $helper;

    protected $operationParameters = [self::PARAMETER_TYPE_ARGUMENT => [], self::PARAMETER_TYPE_PROPERTY => []];
    protected $operationPaginationProperties = [];
    protected $accessAgentType = '';
    protected $scope = '';
    protected $operationType = '';
    protected $operationName = '';
    protected $isOperationListType = false;

    /**
     * GraphQLOperation constructor.
     * @param GraphQLService $graphQLService
     * @param Helper|null $helper
     */
    public function __construct(GraphQLService $graphQLService, Helper $helper = null)
    {
        $this->graphQLService = $graphQLService;
        $this->helper = $helper ?: new Helper();
    }

    /**
     * @param array $operationArguments
     * @return $this
     */
    public function arguments(array $operationArguments)
    {
        // arguments are always merged, since hidden required arguments (e.g. project_id) may have been set in prepareExecution
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, $operationArguments);

        return $this;
    }

    /**
     * @param array $operationProperties
     * @return $this
     */
    public function properties(array $operationProperties)
    {
        // properties are always be overwritten to avoid merging them with suggested default parameters in prepareExecution
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_PROPERTY, $operationProperties, null, false);

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function paginate(int $limit = 0, int $offset = 0)
    {
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, 'limit', $limit);
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, 'offset', $offset);

        $this->operationPaginationProperties = [
            'pagination' => [
                'value' => 'limit,offset,count,total_count,page_count,page_current',
            ]
        ];

        return $this;
    }

    /**
     * @param string $searchString
     * @return $this
     */
    public function search(string $searchString)
    {
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, 'search', $searchString);

        return $this;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function locale(string $locale)
    {
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, 'locale', $locale);

        return $this;
    }

    /**
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function sort(string $field, string $direction = 'ASC')
    {
        $this->hydrateOperationParameters(
            self::PARAMETER_TYPE_ARGUMENT,
            'sort_by',
            [
                'type' => 'object',
                'value' => '[{field:"' . $field . '",direction:' . (strtolower($direction) === 'asc' ? 'asc' : 'desc') . '}]',
            ]
        );

        return $this;
    }

    /**
     * @return ApiResponse
     */
    public function execute()
    {
        $arguments = $this->operationParameters[self::PARAMETER_TYPE_ARGUMENT];
        $properties = array_merge($this->operationPaginationProperties, $this->operationParameters[self::PARAMETER_TYPE_PROPERTY]);

        if ($this->isOperationListType && !isset($properties['items'])) {
            $properties = ['items' => $properties];
        }

        $ret = $this->graphQLService->assembleAndDispatchGraphQLCall(
            $this->accessAgentType,
            $this->scope,
            $this->operationType,
            $this->operationName,
            $arguments,
            $properties
        );

        // Clear all parameters
        $this->prepareExecution('', '', '', '');
        $this->clearOperationParameters();

        return $ret;
    }

    // ### INTERNAL HELPERS ###

    /**
     * @param string $accessAgentType
     * @param string $scope
     * @param string $operationType
     * @param string $operationName
     * @param array $operationArguments
     * @param array $operationProperties
     * @param bool $isListedOperation
     * @return $this
     */
    protected function prepareExecution(
        string $accessAgentType,
        string $scope,
        string $operationType,
        string $operationName,
        array $operationArguments = [],
        array $operationProperties = [],
        bool $isListedOperation = false
    )
    {
        $this->accessAgentType = $accessAgentType;
        $this->scope = $scope;
        $this->operationType = $operationType;
        $this->operationName = $operationName;
        $this->isOperationListType = $isListedOperation;

        $this->hydrateOperationParameters(self::PARAMETER_TYPE_ARGUMENT, $operationArguments);
        $this->hydrateOperationParameters(self::PARAMETER_TYPE_PROPERTY, $operationProperties);

        return $this;
    }

    /**
     * @param $parameterType
     * @param $key
     * @param null $value
     * @param bool $isMerge
     */
    private function hydrateOperationParameters($parameterType, $key, $value = null, $isMerge = true)
    {
        if (is_array($key)) {

            $this->operationParameters[$parameterType] = $isMerge ? array_merge($this->operationParameters[$parameterType], $key) : $key;

        } elseif ($key) {

            if (false === $isMerge) {
                $this->clearOperationParameters($parameterType);
            }

            $this->operationParameters[$parameterType][$key] = $value;
        }
    }

    /**
     * @param null $parameterType
     */
    private function clearOperationParameters($parameterType = null): void
    {
        if ($parameterType && isset($this->operationParameters[$parameterType])) {
            $this->operationParameters[$parameterType] = [];
        } else {
            $this->operationParameters[self::PARAMETER_TYPE_ARGUMENT] = [];
            $this->operationParameters[self::PARAMETER_TYPE_PROPERTY] = [];
        }

        $this->isOperationListType = false;
    }

}
