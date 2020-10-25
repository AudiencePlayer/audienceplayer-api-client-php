<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use AudiencePlayer\AudiencePlayerApiClient\Exceptions\CustomException;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Helper;
use Tests\TestCase;

class GraphQLServiceTest extends TestCase
{
    /**
     * @dataProvider assembleAndDispatchGraphQLCallDataProvider
     * @param $serviceProperties
     * @param $query
     * @param $curlResponse
     * @param $expectedDataResult
     * @param $expectedErrorCode
     */
    public function testAssembleAndDispatchGraphQLCall($serviceProperties, $query, $curlResponse, $expectedDataResult, $expectedErrorCode)
    {
        $helper = $this->fetchClassMock(Helper::class, ['dispatchCurlCall' => $curlResponse]);
        $graphQLService = $this->createGraphQLService(['methods' => ['assembleGraphQLQueryString' => $query], 'constructorArgs' => [$helper], 'partialMock' => true]);

        foreach ($serviceProperties as $serviceProperty => $value) {
            $this->setProtectedProperty($graphQLService, $serviceProperty, $value);
        }

        $result = $graphQLService->assembleAndDispatchGraphQLCall(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'UserDetails',
            [],
            []
        );

        $this->assertTrue(get_class($result) === ApiResponse::class);

        $this->assertSame($expectedDataResult, $result->getData(true));

        if ($expectedErrorCode === Globals::STATUS_GENERAL_OK) {
            $this->assertFalse($result->hasErrors());
        } else {
            $this->assertTrue($result->hasErrors());
            $this->assertSame($expectedErrorCode, $result->getFirstErrorCode());
        }

    }

    public function assembleAndDispatchGraphQLCallDataProvider()
    {
        // $serviceProperties, $query, $curlResponse, $expectedDataResult, $expectedErrorCode
        return [
            // with incorrect initial configuration
            [[], 'query{UserDetails}', 'foobar', null, Globals::STATUS_CONFIG_ERROR],
            [['apiBaseUrl' => ''], 'query{UserDetails}', 'foobar', null, Globals::STATUS_CONFIG_ERROR],
            // with incorrect query-assembly
            [[], '', 'foobar', null, Globals::STATUS_ARGUMENT_ERROR],
            // with unparsable API result
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', null, null, Globals::STATUS_API_RESPONSE_PARSE_ERROR],
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', 'foobar', null, Globals::STATUS_API_RESPONSE_PARSE_ERROR],
            // with unexpected API result
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', '{"foobar":true}', null, Globals::STATUS_API_RESPONSE_FORMAT_ERROR],
            // with properly formatted API result
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', '{"data":123,"errors":[{"code":456}]}', 123, 456],
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', '{"data":null}', null, Globals::STATUS_GENERAL_OK],
            [['apiBaseUrl' => 'foobar', 'projectId' => 1], 'query{UserDetails}', '{"data":123}', 123, Globals::STATUS_GENERAL_OK],
        ];
    }

    /**
     * @dataProvider dispatchGraphQLCallDataProvider
     * @param $isExecuteAsPostRequest
     * @param $isResponseAsObject
     * @param $isWithVariables
     * @param $scope
     * @param $curlResponse
     * @param $expectedResult
     * @param $expectedErrorCode
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::dispatchGraphQLCall
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\AudiencePlayerApiClient::executeRawGraphQLCall
     */
    public function testDispatchGraphQLCall($isExecuteAsPostRequest, $isResponseAsObject, $isWithVariables, $scope, $curlResponse, $expectedResult, $expectedErrorCode)
    {
        $helper = $this->fetchClassMock(Helper::class, ['dispatchCurlCall' => $curlResponse]);
        $graphQLService = new GraphQLService($helper);

        $this->setProtectedProperty($graphQLService, 'projectId', 1);
        $this->setProtectedProperty($graphQLService, 'apiBaseUrl', 'https://example.com');
        $this->setProtectedProperty($graphQLService, 'isExecuteAsPostRequest', [
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION => $isExecuteAsPostRequest,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY => $isExecuteAsPostRequest,
        ]);

        $result = $graphQLService->dispatchGraphQLCall(
            $scope,
            'UserDetails{id}',
            $isWithVariables ? ['foo' => 'bar'] : [],
            $isExecuteAsPostRequest,
            $isResponseAsObject,
            'UserDetails',
            'foobar'
        );

        if ($expectedErrorCode === Globals::STATUS_GENERAL_OK) {
            $this->assertEquals($expectedResult, $result);
        } else {
            $this->assertNull($result->data);
            $this->assertObjectHasAttribute('errors', $result);
            $this->assertSame($expectedErrorCode, $result->errors[0]->code);
        }
    }

    public function dispatchGraphQLCallDataProvider()
    {
        // $isExecuteAsPostRequest, $isResponseAsObject, $isWithVariables, $scope, $curlResponse, $expectedResult, $expectedErrorCode
        return [
            // with expected config error due to incorrect scope
            [true, true, true, '', '', null, Globals::STATUS_CONFIG_ERROR],
            // with dispatch exception
            [true, true, true, Globals::OAUTH_SCOPE_USER, 'throwException', null, Globals::STATUS_API_DISPATCH_EXCEPTION],
            // with expected successfully parsed result json
            [true, false, true, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', '{"foobar":true}', Globals::STATUS_GENERAL_OK],
            [false, false, true, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', '{"foobar":true}', Globals::STATUS_GENERAL_OK],
            [false, false, false, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', '{"foobar":true}', Globals::STATUS_GENERAL_OK],
            // with expected successfully parsed result object
            [true, true, true, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', (object)['foobar' => true], Globals::STATUS_GENERAL_OK],
            [false, true, true, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', (object)['foobar' => true], Globals::STATUS_GENERAL_OK],
            [false, true, false, Globals::OAUTH_SCOPE_USER, '{"foobar":true}', (object)['foobar' => true], Globals::STATUS_GENERAL_OK],
            // without dispatch exception, with malformed but successful query
            [true, true, true, Globals::OAUTH_SCOPE_USER, 'foobar', null, Globals::STATUS_GENERAL_OK],
            [true, true, true, Globals::OAUTH_SCOPE_USER, '{{bad-json', null, Globals::STATUS_GENERAL_OK],
        ];
    }

    /**
     * @dataProvider parseBearerTokenDataProvider
     * @param $bearerToken
     * @param $tokenComponent
     * @param $expectedResult
     */
    public function testParseBearerToken($bearerToken, $tokenComponent, $expectedResult)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertEquals($expectedResult, $graphQLService->parseBearerToken($bearerToken, $tokenComponent));
    }

    public function parseBearerTokenDataProvider()
    {
        $token = $this->createBearerToken([
            'typ' => 'JWT',
            'alg' => 'RS256',
            'jti' => 'a1b2c3d4e5f6',
            'exp' => 123456,
            'scopes' => ['foo'],
            'body' => 'body-phpunit-signature',
        ]);

        // $bearerToken, $tokenComponent, $expectedResult
        return [
            // with empty token
            [
                '',
                0,
                null,
            ],
            // with corrupted token
            [
                'abcdefgh',
                0,
                null,
            ],
            // with valid header
            [
                $token,
                0,
                (object)['typ' => 'JWT', 'alg' => 'RS256', 'jti' => 'a1b2c3d4e5f6'],
            ],
            // with valid payload
            [
                $token,
                1,
                (object)['exp' => 123456, 'scopes' => ['foo']],
            ],
            // with valid signature
            [
                $token,
                2,
                'body-phpunit-signature',
            ],
        ];
    }

    /**
     * @dataProvider validateBearerTokenExpiryDataProvider
     * @param string $bearerToken
     * @param string $scope
     * @param int $minimumTtl
     * @param bool $expectedResult
     */
    public function testValidateBearerTokenExpiry(string $bearerToken, string $scope, int $minimumTtl, bool $expectedResult)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertEquals($expectedResult, $graphQLService->validateBearerTokenExpiry($bearerToken, $scope, $minimumTtl));
    }

    public function validateBearerTokenExpiryDataProvider()
    {
        return [
            // with valid token, with scopes, within ttl maximum
            [
                $this->createBearerToken(['exp' => time() + 3600, 'scopes' => [Globals::OAUTH_SCOPE_USER]]),
                Globals::OAUTH_SCOPE_USER,
                60,
                true
            ],
            // with valid token, without scopes, within ttl maximum
            [
                $this->createBearerToken(['exp' => time() + 3600]),
                '',
                60,
                true
            ],
            // with valid token, without scopes, outside ttl maximum
            [
                $this->createBearerToken(['exp' => time() + 3600]),
                '',
                7200,
                false
            ],
            // with invalid token, without scopes, outside ttl maximum
            [
                $this->createBearerToken(['exp' => time() - 60]),
                '',
                0,
                false
            ],
            // with corrupted token
            [
                'abcdefg',
                '',
                0,
                false
            ],
        ];
    }

    /**
     * @dataProvider fetchPropertyMethodsDataProvider
     * @param $protectedPropertyName
     * @param $expectedDefaultValue
     * @param $setValue
     * @param $expectedValue
     * @param $methodName
     * @param $methodArgs
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchIsExecuteAsPostRequest
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchLocale
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchProjectId
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchApiBaseUrl
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchOAuthClientId
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchOAuthClientSecret
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchBearerToken
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchLastOperationVariables
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchLastOperationQuery
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::fetchLastOperationResult
     */
    public function testFetchPropertyMethods($protectedPropertyName, $expectedDefaultValue, $setValue, $expectedValue, $methodName, $methodArgs)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertClassGetMethod($graphQLService, $protectedPropertyName, $expectedDefaultValue, $setValue, $expectedValue, $methodName, $methodArgs);
    }

    public function fetchPropertyMethodsDataProvider()
    {
        // $protectedPropertyName, $expectedDefaultValue, $setValue, $expectedValue, $methodName, $methodArgs
        return [
            ['locale', null, 'foobar', 'foobar', null, null],
            ['projectId', null, 1, 1, null, null],
            ['apiBaseUrl', null, 'https://example.com', 'https://example.com', null, null],
            ['oauthClientId', null, '123', '123', null, null],
            ['oauthClientSecret', null, 'a1b2c3d4', 'a1b2c3d4', null, null],
            ['lastOperationQuery', null, 123, '123', 'fetchLastOperationQuery', null],
            ['lastOperationVariables', null, [1, 2, 3], [1, 2, 3], 'fetchLastOperationVariables', null],
            ['lastOperationVariables', null, 123, [123], 'fetchLastOperationVariables', null],
            ['lastOperationVariables', null, null, [], 'fetchLastOperationVariables', null],
            ['lastOperationResult', null, [1, 2, 3], [1, 2, 3], 'fetchLastOperationResult', null],
            ['lastOperationResult', null, 123, [123], 'fetchLastOperationResult', null],
            ['lastOperationResult', null, null, [], 'fetchLastOperationResult', null],
            [
                'isExecuteAsPostRequest',
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => true, Globals::GRAPHQL_OPERATION_TYPE_QUERY => true],
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => false, Globals::GRAPHQL_OPERATION_TYPE_QUERY => true],
                false,
                'fetchIsExecuteAsPostRequest',
                Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            ],
            [
                'isExecuteAsPostRequest',
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => true, Globals::GRAPHQL_OPERATION_TYPE_QUERY => true],
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => true, Globals::GRAPHQL_OPERATION_TYPE_QUERY => false],
                false,
                'fetchIsExecuteAsPostRequest',
                Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            ],
            [
                'bearerTokens',
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => 'foobar1', Globals::OAUTH_ACCESS_AS_AGENT_USER => 'foobar2'],
                'foobar1',
                'fetchBearerToken',
                Globals::OAUTH_ACCESS_AS_AGENT_CLIENT,
            ],
            [
                'bearerTokens',
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => 'foobar1', Globals::OAUTH_ACCESS_AS_AGENT_USER => 'foobar2'],
                'foobar2',
                'fetchBearerToken',
                Globals::OAUTH_ACCESS_AS_AGENT_USER,
            ],
        ];
    }

    /**
     * @dataProvider setMethodsDataProvider
     * @param $protectedPropertyName
     * @param $expectedDefaultValue
     * @param $expectedValue
     * @param $methodName
     * @param $methodArgs
     * @param $isSplatMethodArgs
     * @param $methodReturnValue
     * @throws CustomException
     * @throws \ReflectionException
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setLocale
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setProjectId
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setApiBaseUrl
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setOAuthClient
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setBearerToken
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService::setIsExecuteAsPostRequest
     */
    public function testSetPropertyMethods($protectedPropertyName, $expectedDefaultValue, $expectedValue, $methodName, $methodArgs, $isSplatMethodArgs, $methodReturnValue)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertClassSetMethod($graphQLService, $protectedPropertyName, $expectedDefaultValue, $expectedValue, $methodName, $methodArgs, $isSplatMethodArgs, $methodReturnValue);

        // specific tests for setProjectId
        if ($protectedPropertyName === 'projectId') {

            $this->assertException(CustomException::class, function () use ($graphQLService) {
                $graphQLService->setProjectId(0);
            });
        }

        // specific tests for setApiBaseUrl
        if ($protectedPropertyName === 'apiBaseUrl') {

            $this->assertSame('http://foobar.com', $graphQLService->setApiBaseUrl('http://foobar.com/////'));
            $this->assertSame('http://foobar.com', $this->accessProtectedProperty($graphQLService, 'apiBaseUrl'));

            $this->assertException(CustomException::class, function () use ($graphQLService) {
                $graphQLService->setApiBaseUrl('foobar');
            });
            $this->assertException(CustomException::class, function () use ($graphQLService) {
                $graphQLService->setApiBaseUrl('');
            });
        }

        // specific tests for setOAuthClient
        if ($protectedPropertyName === 'oauthClientId') {
            $this->assertException(CustomException::class, function () use ($graphQLService) {
                $graphQLService->setOAuthClient('foobar1', '');
            });
        } elseif ($protectedPropertyName === 'oauthClientSecret') {
            $this->assertException(CustomException::class, function () use ($graphQLService) {
                $graphQLService->setOAuthClient('', 'foobar2');
            });
        }
    }

    public function setMethodsDataProvider()
    {
        // $protectedPropertyName, $expectedDefaultValue, $expectedValue, $methodName, $methodArgs, $isSplatMethodArgs, $methodReturnValue
        return [
            // test setLocale
            ['locale', null, 'en', 'setLocale', 'en', false, 'en'],
            // test setProjectId
            ['projectId', null, 1, 'setProjectId', 1, false, 1],
            // test setApiBaseUrl
            ['apiBaseUrl', null, 'https://example.com', 'setApiBaseUrl', 'https://example.com', false, 'https://example.com'],
            // test setOAuthClient
            ['oauthClientId', null, 'foobar1', 'setOAuthClient', ['foobar1', 'foobar2'], true, null],
            ['oauthClientSecret', null, 'foobar2', 'setOAuthClient', ['foobar1', 'foobar2'], true, null],
            // test setBearerToken
            [
                'bearerTokens',
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => 'foobar1', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                'setBearerToken',
                ['foobar1', Globals::OAUTH_ACCESS_AS_AGENT_CLIENT],
                true,
                'foobar1',
            ],
            [
                'bearerTokens',
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => 'foobar2'],
                'setBearerToken',
                ['foobar2', Globals::OAUTH_ACCESS_AS_AGENT_USER],
                true,
                'foobar2',
            ],
            [
                'bearerTokens',
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                [Globals::OAUTH_ACCESS_AS_AGENT_CLIENT => '', Globals::OAUTH_ACCESS_AS_AGENT_USER => ''],
                'setBearerToken',
                ['foobar', 'non-existing-access-agent-type'],
                true,
                false
            ],
            // test setIsExecuteAsPostRequest
            [
                'isExecuteAsPostRequest',
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => true, Globals::GRAPHQL_OPERATION_TYPE_QUERY => true],
                [Globals::GRAPHQL_OPERATION_TYPE_MUTATION => false, Globals::GRAPHQL_OPERATION_TYPE_QUERY => false],
                'setIsExecuteAsPostRequest',
                [false, false],
                true,
                null,
            ],
        ];
    }

    /**
     * @dataProvider parseGraphQLArgsFromArrayDataProvider
     * @param $args
     * @param $withParentheses
     * @param $expectedResult
     * @throws \ReflectionException
     */
    public function testParseGraphQLArgsFromArray($args, $withParentheses, $expectedResult)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertEquals($expectedResult, $this->accessProtectedMethod($graphQLService, 'parseGraphQLArgsFromArray', [
            $args, $withParentheses
        ]));
    }

    public function parseGraphQLArgsFromArrayDataProvider()
    {
        return [
            [[], true, '',],
            [[], false, '',],
            [['lorem' => 1], true, '(lorem:1)',],
            [['lorem' => 1], false, 'lorem:1',],
            [['lorem' => 7.99], true, '(lorem:7.99)'],
            [['lorem' => 'ipsum'], true, '(lorem:"ipsum")',],
            [['lorem' => 'ipsum'], false, 'lorem:"ipsum"',],
            [['lorem' => [1, 2]], false, 'lorem:[1,2]',],
            [['lorem' => ['value' => 'ipsum', 'type' => 'string']], true, '(lorem:"ipsum")'],
            [['lorem' => ['value' => 'ipsum', 'type' => 'enum']], true, '(lorem:ipsum)'],
            [['lorem' => ['value' => 'ipsum', 'type' => 'bool']], true, '(lorem:true)'],
            [['lorem' => ['value' => 'ipsum', 'type' => 'boolean']], true, '(lorem:true)'],
            [['lorem' => ['value' => 'ipsum', 'type' => 'null']], true, '(lorem:null)'],
            [['lorem' => ['value' => 'ipsum', 'type' => 'null']], true, '(lorem:null)'],
            [['lorem' => ['value' => '[1,2,3]', 'type' => 'array']], true, '(lorem:[1,2,3])'],
            [['lorem' => ['value' => [1, 2, 3], 'type' => 'array']], true, '(lorem:[1,2,3])'],
            [['lorem' => ['value' => [1, '2', 3], 'type' => 'array']], true, '(lorem:["1","2","3"])'],
            [['price' => 7.99, 'lorem' => ['value' => [1, '2', 3], 'type' => 'array'], 'foo' => 'bar'], true, '(price:7.99,lorem:["1","2","3"],foo:"bar")'],
        ];
    }

    /**
     * @dataProvider parseGraphQLPropsFromArrayDataProvider
     * @param $args
     * @param $withBraces
     * @param $expectedResult
     * @throws \ReflectionException
     */
    public function testParseGraphQLPropsFromArray($args, $withBraces, $expectedResult)
    {
        $graphQLService = $this->createGraphQLService();
        $this->assertEquals($expectedResult, $this->accessProtectedMethod($graphQLService, 'parseGraphQLPropsFromArray', [
            $args, $withBraces
        ]));
    }

    public function parseGraphQLPropsFromArrayDataProvider()
    {
        return [
            [[], true, '',],
            [[], false, '',],
            [['lorem' => 1], true, '{lorem{1}}',],
            [['lorem' => 1], false, 'lorem{1}',],
            [['lorem' => 'ipsum'], true, '{lorem{ipsum}}',],
            [['lorem' => 'ipsum'], false, 'lorem{ipsum}',],
            [['lorem', 'ipsum'], true, '{lorem,ipsum}',],
            [['lorem', 'ipsum'], false, 'lorem,ipsum',],
            [['lorem', [1, 'ipsum', 3]], true, '{lorem,1,ipsum,3}',],
            [['lorem' => [1, 'ipsum', 3]], false, 'lorem{1,ipsum,3}',],
        ];
    }

    public function testAssembleGraphQLQueryString()
    {
        $graphQLService = $this->createGraphQLService([
            'methods' => [
                'parseGraphQLArgsFromArray' => '<ARGS>',
                'parseGraphQLPropsFromArray' => '<PROPS>',
            ],
            'partialMock' => true
        ]);

        $result = $this->accessProtectedMethod($graphQLService, 'assembleGraphQLQueryString', [
            Globals::GRAPHQL_OPERATION_TYPE_QUERY, 'UserDetails', [], ['id', 'items{id}']
        ]);

        $this->assertSame(
            'query{UserDetails<ARGS>{<PROPS>}}',
            $result
        );
    }

    /**
     * @dataProvider assembleApiUrlDataProvider
     * @param $scope
     * @param $apiBaseUrl
     * @param $projectId
     * @param $expectedResult
     * @throws \ReflectionException
     */
    public function testAssembleApiUrl($scope, $apiBaseUrl, $projectId, $expectedResult)
    {
        $graphQLService = $this->createGraphQLService(['methods' => ['parseGraphQLArgsFromArray' => '(foo:bar)'], 'partialMock' => true]);
        $this->setProtectedProperty($graphQLService, 'apiBaseUrl', $apiBaseUrl);
        $this->setProtectedProperty($graphQLService, 'projectId', $projectId);
        $this->assertSame($expectedResult, $this->accessProtectedMethod($graphQLService, 'assembleApiUrl', [$scope]));
    }

    public function assembleApiUrlDataProvider()
    {
        return [
            [Globals::OAUTH_SCOPE_USER, 'https://example.com', 0, null],
            [Globals::OAUTH_SCOPE_USER, 'https://example.com', 1, 'https://example.com/graphql/1/default'],
            [Globals::OAUTH_SCOPE_USER, 'https://example.com', 2, 'https://example.com/graphql/2/default'],
            [Globals::OAUTH_SCOPE_USER, 'https://example.com/', 3, 'https://example.com//graphql/3/default'],
            [Globals::OAUTH_SCOPE_ADMIN, 'https://example.com', 0, 'https://example.com/graphql/core/admin'],
            [Globals::OAUTH_SCOPE_ADMIN, 'https://example.com', 1, 'https://example.com/graphql/core/admin'],
            [Globals::OAUTH_SCOPE_ADMIN, 'https://example.com', 2, 'https://example.com/graphql/core/admin'],
            [Globals::OAUTH_SCOPE_ADMIN, 'https://example.com/', 3, 'https://example.com//graphql/core/admin'],
        ];
    }

}
