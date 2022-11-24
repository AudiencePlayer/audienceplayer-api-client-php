<?php

declare(strict_types=1);

namespace Tests;

use AudiencePlayer\AudiencePlayerApiClient\AudiencePlayerApiClient;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperation;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationMutation;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationQuery;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLService;
use Closure;
use Exception;

trait TestCaseMockHelper
{
    public
        $apiClient,
        $graphQLService,
        $graphQLOperation,
        $graphQLOperationMutation,
        $graphQLOperationQuery;

    public function fetchClassMock($className, array $mockMethods = [], array $constructorArgs = [], $partialMock = false)
    {
        // Instantiate the mock builder
        $mock = $this->getMockBuilder($className);

        if ($constructorArgs) {
            $mock = $mock->setConstructorArgs($constructorArgs);
        } else {
            $mock = $mock->disableOriginalConstructor();
        }

        // When partial mocking, setMethods declares the mockable (public only!) methods, all others are left intact
        if ($partialMock && $mockMethods) {
            $mock = $mock->setMethods(array_keys($mockMethods));
        }

        $mock = $mock->getMock();

        // Specify method return values
        foreach ($mockMethods as $method => $willReturn) {
            if ($willReturn === 'throwException') {
                $mock->method($method)->willThrowException(new Exception());
            } else {
                $mock->method($method)->willReturn($willReturn);
            }
        }

        return $mock;
    }

    public function createGraphQLService(array $mockSettings = []): GraphQLService
    {
        if ($mockSettings) {
            return $this->graphQLService = $this->fetchClassMock(GraphQLService::class, $mockSettings['methods'] ?? [], $mockSettings['constructorArgs'] ?? [], $mockSettings['partialMock'] ?? false);
        } else {
            return $this->graphQLService = new GraphQLService();
        }
    }

    public function createGraphQLOperation(array $mockProperties = []): GraphQLOperation
    {
        if ($mockProperties) {
            return $this->graphQLOperation = $this->fetchClassMock(GraphQLOperation::class, $mockProperties['methods'] ?? [], $mockProperties['constructorArgs'] ?? [], $mockProperties['partialMock'] ?? false);
        } else {
            return $this->graphQLOperation = new GraphQLOperation($this->graphQLService ?? $this->createGraphQLService());
        }
    }

    public function createGraphQLOperationMutation(array $mockProperties = []): GraphQLOperationMutation
    {
        if ($mockProperties) {
            return $this->graphQLOperationMutation = $this->fetchClassMock(GraphQLOperationMutation::class, $mockProperties['methods'] ?? [], $mockProperties['constructorArgs'] ?? [], $mockProperties['partialMock'] ?? false);
        } else {
            return $this->graphQLOperationMutation = new GraphQLOperationMutation($this->graphQLService ?? $this->createGraphQLService());
        }
    }

    public function createGraphQLOperationQuery(array $mockProperties = []): GraphQLOperationQuery
    {
        if ($mockProperties) {
            return $this->graphQLOperationQuery = $this->fetchClassMock(GraphQLOperationQuery::class, $mockProperties['methods'] ?? [], $mockProperties['constructorArgs'] ?? [], $mockProperties['partialMock'] ?? false);
        } else {
            return $this->graphQLOperationQuery = new GraphQLOperationQuery($this->graphQLService ?? $this->createGraphQLService());
        }
    }

    public function createApiClient(array $graphQLServiceMockSettings = [], array $graphQLOperationMutationMockProperties = [], array $graphQLOperationQueryMockProperties = []): AudiencePlayerApiClient
    {
        $this->createGraphQLService($graphQLServiceMockSettings);
        $this->createGraphQLOperationMutation($graphQLOperationMutationMockProperties);
        $this->createGraphQLOperationQuery($graphQLOperationQueryMockProperties);

        return $this->apiClient = new AudiencePlayerApiClient($this->graphQLService, $this->graphQLOperationMutation, $this->graphQLOperationQuery);
    }


    public function createBearerToken(array $properties = [])
    {
        $parts = [
            // JWT: header
            (object)[
                'typ' => $properties['typ'] ?? 'JWT',
                'alg' => $properties['alg'] ?? 'RS256',
                'jti' => $properties['jti'] ?? 'a1b2c3d4e5f6',
            ],
            //JWT: payload
            (object)[
                'exp' => $properties['exp'] ?? time() + 60,
                'scopes' => $properties['scopes'] ?? [],
            ],
            // JWT: signature
            $properties['body'] ?? md5('body'),
        ];

        return
            base64_encode(json_encode($parts[0])) . '.' .
            base64_encode(json_encode($parts[1])) . '.' .
            base64_encode(json_encode($parts[2]));
    }

    /**
     * Helper to access protected class properties that need to be tested
     *
     * @param $obj
     * @param $prop
     * @return mixed
     * @throws \ReflectionException
     */
    public function accessProtectedProperty($obj, $prop)
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * Helper to set protected class properties that need to be tested
     *
     * @param $obj
     * @param $prop
     * @param $value
     * @return void
     * @throws \ReflectionException
     */
    public function setProtectedProperty($obj, $prop, $value)
    {
        $reflection = new \ReflectionObject($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /**
     * Helper to access protected/private method sthat needs to be tested
     *
     * @param $obj
     * @param $method
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    public function accessProtectedMethod($obj, $method, $args = [])
    {
        $reflection = new \ReflectionClass($obj);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * @param $classInstance
     * @param $protectedPropertyName
     * @param $expectedDefaultValue
     * @param $expectedValue
     * @param string $methodName
     * @param null $methodArgs
     * @param bool $isSplatMethodArgs
     * @param null $methodReturnValue
     * @throws \ReflectionException
     */
    public function assertClassSetMethod(
        $classInstance,
        $protectedPropertyName,
        $expectedDefaultValue,
        $expectedValue,
        $methodName = '',
        $methodArgs = null,
        $isSplatMethodArgs = false,
        $methodReturnValue = null
    )
    {
        $methodName = $methodName ?: 'set' . ucfirst($protectedPropertyName);
        $this->assertEquals($expectedDefaultValue, $this->accessProtectedProperty($classInstance, $protectedPropertyName));

        if ($isSplatMethodArgs) {
            $this->assertEquals($methodReturnValue, $classInstance->{$methodName}(...$methodArgs));
        } else {
            $this->assertEquals($methodReturnValue, $classInstance->{$methodName}($methodArgs));
        }

        $this->assertEquals($expectedValue, $this->accessProtectedProperty($classInstance, $protectedPropertyName));
    }

    public function assertClassGetMethod(
        $classInstance,
        $protectedPropertyName,
        $expectedDefaultValue,
        $setValue, $expectedValue,
        $methodName = '',
        $methodArgs = null,
        $isSplatMethodArgs = false
    )
    {
        $methodName = $methodName ?: 'fetch' . ucfirst($protectedPropertyName);
        $this->assertEquals($expectedDefaultValue, $this->accessProtectedProperty($classInstance, $protectedPropertyName));
        $this->setProtectedProperty($classInstance, $protectedPropertyName, $setValue);

        if ($methodArgs) {
            if ($isSplatMethodArgs) {
                $this->assertEquals($expectedValue, $classInstance->{$methodName}(...$methodArgs));
            } else {
                $this->assertEquals($expectedValue, $classInstance->{$methodName}($methodArgs));
            }
        } else {
            $this->assertEquals($expectedValue, $classInstance->{$methodName}());
        }
    }

    public function assertException(string $expectedExceptionClass, Closure $closure)
    {
        $exceptionClass = null;

        try {
            $closure();
        } catch (\Exception $e) {
            $exceptionClass = get_class($e);
        }

        $this->assertSame($expectedExceptionClass, $exceptionClass);

    }

}
