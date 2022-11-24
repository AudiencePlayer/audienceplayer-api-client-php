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

namespace AudiencePlayer\AudiencePlayerApiClient\Resources;

use AudiencePlayer\AudiencePlayerApiClient\Exceptions\CustomException;
use Exception;

class ApiResponse
{
    protected
        $data,
        $errors = [];

    private
        $parsingStatus = false,
        $rawInputResult,
        $parsedInputResult,
        $operationName,
        $operationQuery,
        $operationVariables;

    /**
     * ApiResponse constructor.
     * @param $rawInputResult
     * @param string $operationName
     * @param string $operationQuery
     * @param array $operationVariables
     */
    public function __construct($rawInputResult, string $operationName = '', string $operationQuery = '', array $operationVariables = [])
    {
        $this->parsingStatus = $this->parseData($rawInputResult, $operationName);
        $this->operationName = $operationName;
        $this->operationQuery = $operationQuery;
        $this->operationVariables = $operationVariables;
    }

    /**
     * @param $rawInputResult
     * @param string $operationName
     * @return bool
     */
    protected function parseData($rawInputResult, string $operationName = ''): bool
    {
        $ret = true;
        $this->rawInputResult = $rawInputResult;

        try {

            if ($rawInputResult) {

                if (is_string($rawInputResult)) {

                    if (is_null($this->parsedInputResult = json_decode($rawInputResult))) {
                        throw new CustomException('json_decode exception (php < 7.3)', Globals::STATUS_GENERAL_ERROR);
                    }

                } else {
                    $this->parsedInputResult = (object)$rawInputResult;
                }

            } else {
                throw new CustomException('rawInputResult is empty', Globals::STATUS_GENERAL_ERROR);
            }

        } catch (Exception $e) {

            // handle error
            $this->parsedInputResult = self::assembleDefaultErrorResponse(
                $operationName,
                Globals::MSG_API_RESPONSE_PARSE_ERROR,
                Globals::STATUS_API_RESPONSE_PARSE_ERROR
            );

            $ret = false;
        }

        if (
            false === property_exists((object)$this->parsedInputResult, 'data') &&
            false === property_exists((object)$this->parsedInputResult, 'errors')
        ) {
            $this->parsedInputResult = self::assembleDefaultErrorResponse(
                $operationName,
                Globals::MSG_API_RESPONSE_FORMAT_ERROR,
                Globals::STATUS_API_RESPONSE_FORMAT_ERROR
            );

            $ret = false;
        }

        // set data property (if applicable)
        if (property_exists($this->parsedInputResult, 'data')) {
            $this->data = $this->parsedInputResult->data;
        }

        // set errors property (if applicable)
        if (property_exists($this->parsedInputResult, 'errors')) {
            $this->errors = $this->parsedInputResult->errors ?? [];
        }

        return $ret;
    }

    /**
     * @param bool $isFlattenOperationName
     * @return mixed
     */
    public function getData(bool $isFlattenOperationName = false)
    {
        return ($isFlattenOperationName && $this->operationName && isset($this->data->{$this->operationName})) ?
            $this->parsedInputResult->data->{$this->operationName} : $this->data;
    }

    /**
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawInputResult;
    }

    /**
     * @return mixed
     */
    public function getParsedData()
    {
        return $this->parsedInputResult;
    }


    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return boolval($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors ?: [];
    }

    /**
     * @return null
     */
    public function getFirstErrorCode(): ?int
    {
        return isset($this->errors, $this->errors[0], $this->errors[0]->code) ?
            $this->errors[0]->code : null;
    }

    /**
     * @return string
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * @return string
     */
    public function getOperationQuery(): string
    {
        return $this->operationQuery;
    }

    /**
     * @return array
     */
    public function getOperationVariables(): array
    {
        return $this->operationVariables;
    }

    /**
     * @return bool
     */
    public function isDataParsed(): bool
    {
        return $this->parsingStatus;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->isDataParsed() && false === $this->hasErrors();
    }

    /**
     * @param string $operation
     * @param string $message
     * @param int $code
     * @return object
     */
    public static function assembleDefaultErrorResponse(string $operation, string $message, int $code = Globals::STATUS_GENERAL_ERROR): object
    {
        return (object)[
            'data' => null,
            'errors' => [(object)[
                'operation' => $operation,
                'message' => $message,
                'code' => $code,
            ]],
        ];
    }

}
