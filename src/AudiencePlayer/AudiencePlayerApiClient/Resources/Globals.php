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

namespace AudiencePlayer\AudiencePlayerApiClient\Resources;

class Globals
{
    public const
        OAUTH_SCOPE_USER = 'api-user-access',
        OAUTH_SCOPE_ADMIN = 'api-admin-access',

        OAUTH_ACCESS_AS_AGENT_CLIENT = 'client',
        OAUTH_ACCESS_AS_AGENT_USER = 'user',

        GRAPHQL_OPERATION_TYPE_MUTATION = 'mutation',
        GRAPHQL_OPERATION_TYPE_QUERY = 'query',

        GRAPHQL_OPERATION_SORT_DIRECTION_ASC = 'asc',
        GRAPHQL_OPERATION_SORT_DIRECTION_DESC = 'desc',

        API_RESPONSE_FORMAT_JSON = 'json',
        API_RESPONSE_FORMAT_OBJECT = 'object';

    public const
        OAUTH_SCOPES = [self::OAUTH_SCOPE_ADMIN, self::OAUTH_SCOPE_USER],
        OAUTH_ACCESS_AGENTS = [self::OAUTH_ACCESS_AS_AGENT_CLIENT, self::OAUTH_ACCESS_AS_AGENT_USER];

    public const
        STATUS_GENERAL_ERROR = -1,
        MSG_GENERAL_ERROR = 'General error',

        STATUS_GENERAL_OK = 0,
        MSG_GENERAL_OK = 'OK',

        STATUS_ARGUMENT_ERROR = 4000,
        MSG_ARGUMENT_ERROR = 'User argument error, given query arguments could not be parsed',

        STATUS_CONFIG_ERROR = 4001,
        MSG_CONFIG_ERROR = 'Client configuration error, required configuration arguments are were not properly hydrated',

        STATUS_CONFIG_CLIENT_ID_ERROR = 4002,
        MSG_CONFIG_CLIENT_ID_ERROR = 'Client configuration error, incorrect value for argument "oauthClientId"',

        STATUS_CONFIG_CLIENT_SECRET_ERROR = 4003,
        MSG_CONFIG_CLIENT_SECRET_ERROR = 'Client configuration error, incorrect value for argument "oauthClientSecret"',

        STATUS_CONFIG_PROJECT_ID_ERROR = 4004,
        MSG_CONFIG_PROJECT_ID_ERROR = 'Client configuration error, incorrect value for argument "projectId"',

        STATUS_CONFIG_API_URL_ERROR = 4005,
        MSG_CONFIG_API_URL_ERROR = 'Client configuration error, incorrect value for argument "apiBaseUrl"',

        STATUS_API_DISPATCH_EXCEPTION = 5001,
        MSG_API_DISPATCH_EXCEPTION = 'Client dispatch execution error',

        STATUS_API_RESPONSE_PARSE_ERROR = 5002,
        MSG_API_RESPONSE_PARSE_ERROR = 'Api response error, response could not be parsed',

        STATUS_API_RESPONSE_FORMAT_ERROR = 5003,
        MSG_API_RESPONSE_FORMAT_ERROR = 'Api response error, expected properties data and/or errors not present';
}
