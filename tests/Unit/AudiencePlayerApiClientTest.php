<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\AudiencePlayerApiClient;
use AudiencePlayer\AudiencePlayerApiClient\Exceptions\CustomException;
use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use Tests\TestCase;

class AudiencePlayerApiClientTest extends TestCase
{
    /**
     * @dataProvider hydrateConfigDataProvider
     * @param $instantiationMethod
     * @param $oauthClientId
     * @param $oauthClientSecret
     * @param $projectId
     * @param $apiBaseUrl
     * @param $expectedErrorCode
     * @throws CustomException
     * @throws \ReflectionException
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\AudiencePlayerApiClient::init
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\AudiencePlayerApiClient::hydrateConfig
     */
    public function testHydrateConfig($instantiationMethod, $oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl, $expectedErrorCode)
    {
        if ($expectedErrorCode) {
            $this->expectException(CustomException::class);
            $this->expectExceptionCode($expectedErrorCode);

            if ($instantiationMethod === 'init') {
                AudiencePlayerApiClient::init($oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl);
            } else {
                $apiClient = $this->createApiClient();
                $apiClient->hydrateConfig($oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl);
            }

        } else {

            if ($instantiationMethod === 'init') {
                $apiClient = AudiencePlayerApiClient::init($oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl);
            } else {
                $apiClient = $this->createApiClient();
                $apiClient->hydrateConfig($oauthClientId, $oauthClientSecret, $projectId, $apiBaseUrl);
            }

            $graphQLService = $this->accessProtectedProperty($apiClient, 'graphQLService');
            $this->assertSame($oauthClientId, $graphQLService->fetchOauthClientId());
            $this->assertSame($oauthClientSecret, $graphQLService->fetchOauthClientSecret());
            $this->assertSame($projectId, $graphQLService->fetchProjectId());
            $this->assertSame($apiBaseUrl, $graphQLService->fetchApiBaseUrl());
        }
    }

    public function hydrateConfigDataProvider()
    {
        return [
            ['init', '', 'secret', 1, 'http://example.com', Globals::STATUS_CONFIG_CLIENT_ID_ERROR],
            ['new', '', 'secret', 1, 'http://example.com', Globals::STATUS_CONFIG_CLIENT_ID_ERROR],
            ['init', 'id', '', 1, 'http://example.com', Globals::STATUS_CONFIG_CLIENT_SECRET_ERROR],
            ['new', 'id', '', 1, 'http://example.com', Globals::STATUS_CONFIG_CLIENT_SECRET_ERROR],
            ['init', 'id', 'secret', 0, 'http://example.com', Globals::STATUS_CONFIG_PROJECT_ID_ERROR],
            ['new', 'id', 'secret', 0, 'http://example.com', Globals::STATUS_CONFIG_PROJECT_ID_ERROR],
            ['init', 'id', 'secret', 1, '', Globals::STATUS_CONFIG_API_URL_ERROR],
            ['new', 'id', 'secret', 1, '', Globals::STATUS_CONFIG_API_URL_ERROR],
            ['init', 'id', 'secret', 1, 'http://example.com', null],
            ['new', 'id', 'secret', 1, 'http://example.com', null],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function testHydrateLocale()
    {
        $apiClient = $this->createApiClient();
        $this->assertEquals('', $this->accessProtectedProperty($this->graphQLService, 'locale'));
        $this->assertSame('xx', $apiClient->hydrateLocale('xx'));
        $this->assertEquals('xx', $this->accessProtectedProperty($this->graphQLService, 'locale'));
    }

    /**
     * @dataProvider hydrateValidatedOrRenewedBearerTokenForClientDataProvider
     * @param $bearerToken
     * @param $minimumTtl
     * @param $apiResponse
     * @param $expectedResult
     */
    public function testHydrateValidatedOrRenewedBearerTokenForClient($bearerToken, $minimumTtl, $apiResponse, $expectedResult)
    {
        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => [
                'assembleAndDispatchGraphQLCall' => new ApiResponse(json_encode($apiResponse))
            ], 'partialMock' => true]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $result = $apiClient->hydrateValidatedOrRenewedBearerTokenForClient($bearerToken, $minimumTtl);

        $this->assertSame($expectedResult, $result);
    }

    public function hydrateValidatedOrRenewedBearerTokenForClientDataProvider()
    {
        $validBearerToken = $this->createBearerToken(['exp' => time() + 3600]);
        $expiredBearerToken = $this->createBearerToken(['exp' => time() - 3600]);

        // $bearerToken, $minimumTtl, $apiResponse, $expectedResult
        return [
            // with valid token
            [
                $validBearerToken,
                0,
                null,
                $validBearerToken,
            ],
            // with valid token that expires within given ttl, with successful token renewal
            [
                $validBearerToken,
                7200,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with valid token that expires within given ttl, with failed token renewal
            [
                $validBearerToken,
                7200,
                [
                    null,
                ],
                false,
            ],
            // with invalid token, with successful token renewal
            [
                $expiredBearerToken,
                0,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with invalid token, with failed token renewal
            [
                $expiredBearerToken,
                0,
                null,
                false,
            ],
        ];
    }

    /**
     * @dataProvider hydrateValidatedOrRenewedBearerTokenForUserDataProvider
     * @param $userId
     * @param $userEmail
     * @param $isAutoRegister
     * @param $isAllowRenewal
     * @param $bearerToken
     * @param $minimumTtl
     * @param $apiResponse
     * @param $expectedResult
     */
    public function testHydrateValidatedOrRenewedBearerTokenForUser(
        $userId,
        $userEmail,
        $isAutoRegister,
        $isAllowRenewal,
        $bearerToken,
        $minimumTtl,
        $apiResponse,
        $expectedResult
    )
    {
        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => [
                'assembleAndDispatchGraphQLCall' => new ApiResponse(json_encode($apiResponse))
            ], 'partialMock' => true]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $result = $apiClient->hydrateValidatedOrRenewedBearerTokenForUser(
            $userId,
            $userEmail,
            ['name' => 'phpunit', 'password' => 'foobar', 'locale' => 'en'],
            $bearerToken,
            $isAutoRegister,
            $isAllowRenewal,
            $minimumTtl
        );

        $this->assertSame($expectedResult, $result['access_token']);
    }

    public function hydrateValidatedOrRenewedBearerTokenForUserDataProvider()
    {
        $userEmail = 'phpunit' . $this->fetchCurrentProcessId() . '@example.com';
        $validBearerToken = $this->createBearerToken(['exp' => time() + 3600, 'scopes' => [Globals::OAUTH_SCOPE_USER]]);
        $expiredBearerToken = $this->createBearerToken(['exp' => time() - 3600, 'scopes' => [Globals::OAUTH_SCOPE_USER]]);

        // $userId, $userEmail, $isAutoRegister, $isAllowRenewal, $bearerToken, $minimumTtl, $apiResponse, $expectedResult
        return [
            // with valid token
            [
                1,
                $userEmail,
                true,
                true,
                $validBearerToken,
                0,
                null,
                $validBearerToken,
            ],
            // with valid token that expires within given ttl, with successful token renewal on userId
            [
                1,
                '',
                true,
                true,
                $validBearerToken,
                7200,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with valid token that expires within given ttl, with successful token renewal on userEmail
            [
                0,
                $userEmail,
                true,
                true,
                $validBearerToken,
                7200,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with valid token that expires within given ttl, with disallowed token renewal
            [
                1,
                $userEmail,
                true,
                false,
                $validBearerToken,
                7200,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                null,
            ],
            // with valid token that expires within given ttl, with failed token renewal
            [
                1,
                $userEmail,
                true,
                true,
                $validBearerToken,
                7200,
                null,
                null,
            ],
            // with invalid token, with successful token renewal on userId
            [
                1,
                '',
                true,
                true,
                $expiredBearerToken,
                0,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with invalid token, with successful token renewal on userEmail
            [
                0,
                $userEmail,
                true,
                true,
                $expiredBearerToken,
                0,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                'newBearerToken',
            ],
            // with invalid token, with disallowed token renewal
            [
                1,
                $userEmail,
                true,
                false,
                $expiredBearerToken,
                0,
                (object)['data' => (object)['access_token' => 'newBearerToken']],
                null,
            ],
            // with invalid token, with failed token renewal
            [
                1,
                $userEmail,
                true,
                true,
                $expiredBearerToken,
                0,
                null,
                null,
            ],
        ];
    }

    /**
     * @param $expectedResult
     * @testWith    [true]
     *              [false]
     */
    public function testUpdateUser($expectedResult)
    {
        $apiResponse = $this->fetchClassMock(ApiResponse::class, ['isSuccessful' => $expectedResult]);
        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => [
                'assembleAndDispatchGraphQLCall' => $apiResponse
            ]]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $this->assertSame($expectedResult, $apiClient->updateUser(123, ['name' => 'foobar']));
    }

    /**
     * @param $expectedResult
     * @testWith    [true]
     *              [false]
     */
    public function testDeleteUser($expectedResult)
    {
        $apiResponse = $this->fetchClassMock(ApiResponse::class, ['isSuccessful' => $expectedResult]);
        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => [
                'assembleAndDispatchGraphQLCall' => $apiResponse
            ]]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $this->assertSame($expectedResult, $apiClient->deleteUser(123, 'info@example.com'));
    }

    /**
     * @param $expectedResult
     * @testWith    [true]
     *              [false]
     */
    public function testFetchUser($expectedResult)
    {
        $apiResponse = $this->fetchClassMock(ApiResponse::class, ['isSuccessful' => $expectedResult]);
        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => [
                'assembleAndDispatchGraphQLCall' => $apiResponse
            ]]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $this->assertSame($expectedResult, $apiClient->fetchUser(123, 'info@example.com'));
    }

    /**
     * @param $isErrorsOnly
     * @param $isStringifyResult
     * @testWith    [true, true]
     *              [false, true]
     *              [true, false]
     *              [false, false]
     */
    public function testFetchLastOperationResult($isErrorsOnly, $isStringifyResult)
    {
        $lastOperationResult = [
            'data' => true,
            'errors' => [1]
        ];

        $expectedResult = $isErrorsOnly ? $lastOperationResult['errors'] : $lastOperationResult;

        if ($isStringifyResult) {
            $expectedResult = json_encode($expectedResult);
        }

        $apiClient = new AudiencePlayerApiClient(
            $this->createGraphQLService(['methods' => ['fetchLastOperationResult' => $lastOperationResult]]),
            $this->createGraphQLOperationMutation(),
            $this->createGraphQLOperationQuery()
        );

        $this->setProtectedProperty($this->graphQLService, 'lastOperationResult', $lastOperationResult);


        $this->assertSame($expectedResult, $apiClient->fetchLastOperationResult($isErrorsOnly, $isStringifyResult));
    }

}
