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

class Helper
{
    public function escapeString(string $string, $search = ['"'], $replace = ['\"']): string
    {
        return str_replace($search, $replace, $string);
    }

    /**
     * Checks if array contains only integer values
     *
     * @param array $arr
     * @return bool
     */
    public function hasArrayOnlyIntegers(array $arr): bool
    {
        if ($arr) {

            $isAllNumeric = true;

            foreach ($arr as $value) {

                if (false === is_int($value)) {

                    $isAllNumeric = false;
                    break;
                }
            }

            return $isAllNumeric;

        } else {

            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     * @param $url
     * @param array $options
     * @param string $forwardForIp
     * @return string
     */
    public function dispatchCurlCall($url, array $options = [], string $forwardForIp = ''): string
    {
        $ch = curl_init();

        $curlOptions = $this->prepareCurlRequest($url, $options, $forwardForIp);

        foreach ($curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $s = curl_exec($ch);
        curl_close($ch);

        return strval($s);
    }

    public function parseSingleGraphQLArgument($value, $key = null): string
    {
        $type = $value['type'] ?? gettype($value);
        $value = $value['value'] ?? $value;

        switch (strtolower($type)) {

            case 'string':
                $value = '"' . $this->escapeString(strval($value)) . '"';
                break;

            case 'boolean':
            case 'bool':
                $value = boolval(is_string($value) ? trim(preg_replace('/^(false)$/i', '0', $value)) : $value) ? 'true' : 'false';
                break;

            case 'null':
                $value = 'null';
                break;

            case 'array':
                if (is_array($value)) {

                    if ($this->hasArrayOnlyIntegers($value)) {
                        $arr = $value;
                    } else {
                        $self = $this;
                        $arr = array_map(function ($item) use ($self) {
                            return $self->parseSingleGraphQLArgument($item);
                        }, $value);
                    }

                    $value = '[' . implode(',', $arr) . ']';
                }
                break;

            default:
                $value = strval($value);
                break;
        }

        return $key ? $key . ':' . $value : $value;
    }

    // ### INTERNAL HELPERS ###

    /**
     * @param $url
     * @param array $options
     * @param string $forwardForIp
     * @return array
     */
    protected function prepareCurlRequest($url, array $options = [], string $forwardForIp = ''): array
    {
        if (trim($forwardForIp)) {
            $arrHeaders = ['X-Forwarded-For: ' . trim($forwardForIp)];
            $options['CURLOPT_HTTPHEADER'] = isset($options['CURLOPT_HTTPHEADER']) ? array_merge($options['CURLOPT_HTTPHEADER'], $arrHeaders) : $arrHeaders;
        }

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => isset($options['CURLOPT_HEADER']) ? $options['CURLOPT_HEADER'] : false,
            CURLOPT_HTTPHEADER => isset($options['CURLOPT_HTTPHEADER']) ? $options['CURLOPT_HTTPHEADER'] : [],
            CURLOPT_SSL_VERIFYPEER => isset($options['CURLOPT_SSL_VERIFYPEER']) ? $options['CURLOPT_SSL_VERIFYPEER'] : false,
            CURLOPT_RETURNTRANSFER => isset($options['CURLOPT_RETURNTRANSFER']) ? $options['CURLOPT_RETURNTRANSFER'] : true,

            // some request seem to take a long time, so explicitly set long curl time
            CURLOPT_CONNECTTIMEOUT => isset($options['CURLOPT_CONNECTTIMEOUT']) ? $options['CURLOPT_CONNECTTIMEOUT'] : 30,
            CURLOPT_TIMEOUT => isset($options['CURLOPT_TIMEOUT']) ? $options['CURLOPT_TIMEOUT'] : 60
        ];

        // override user agent
        if (isset($options['CURLOPT_USERAGENT'])) {

            $curlOptions[CURLOPT_USERAGENT] = $options['CURLOPT_USERAGENT'];
        }

        if (isset($options['CURLOPT_USERPWD'])) {

            $curlOptions[CURLOPT_USERPWD] = $options['CURLOPT_USERPWD'];
        }

        if (isset($options['CURLOPT_CUSTOMREQUEST'])) {

            $curlOptions[CURLOPT_CUSTOMREQUEST] = $options['CURLOPT_CUSTOMREQUEST'];
        }

        if (isset($options['CURLOPT_POST']) || isset($options['CURLOPT_POSTFIELDS'])) {

            $curlOptions[CURLOPT_POST] = 1;
            $curlOptions[CURLOPT_POSTFIELDS] = isset($options['CURLOPT_POSTFIELDS']) ? $options['CURLOPT_POSTFIELDS'] : '';
            $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        }

        return $curlOptions;
    }

}
