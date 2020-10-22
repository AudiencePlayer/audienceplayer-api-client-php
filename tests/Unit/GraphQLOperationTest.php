<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use Tests\TestCase;

class GraphQLOperationTest extends TestCase
{
    /**
     * @param $limit
     * @param $offset
     * @throws \ReflectionException
     * @testWith    [0, 0]
     *              [1, 0]
     *              [0, 1]
     *              [1, 1]
     */
    public function testPaginate($limit, $offset)
    {
        $this->createGraphQLOperation()->paginate($limit, $offset);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');

        $this->assertIsArray($operationParameters);
        $this->assertIsArray($operationParameters['argument']);
        $this->assertSame($limit, $operationParameters['argument']['limit']);
        $this->assertSame($offset, $operationParameters['argument']['offset']);
    }

    public function testProperties()
    {
        $properties = ['foo' => 'bar', 1, 'lorem', 0, null];
        $this->createGraphQLOperation()->properties($properties);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');

        $this->assertIsArray($operationParameters);
        $this->assertSame($properties, $operationParameters['property']);
    }

    public function testArguments()
    {
        $arguments1 = ['foo' => 'bar', 1];
        $arguments2 = ['lorem', 0, null];
        $this->createGraphQLOperation();

        $this->graphQLOperation->arguments($arguments1);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertIsArray($operationParameters);
        $this->assertSame($arguments1, $operationParameters['argument']);

        $this->graphQLOperation->arguments($arguments2);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertIsArray($operationParameters);
        $this->assertSame(array_merge($arguments1, $arguments2), $operationParameters['argument']);
    }

    public function testSearch()
    {
        $searchString = 'foobar';
        $this->createGraphQLOperation()->search($searchString);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertIsArray($operationParameters);
        $this->assertSame(['search' => $searchString], $operationParameters['argument']);
    }

    public function testLocale()
    {
        $locale = 'en';
        $this->createGraphQLOperation()->locale($locale);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertIsArray($operationParameters);
        $this->assertSame(['locale' => $locale], $operationParameters['argument']);
    }

    public function testSort()
    {
        $sortField = 'id';
        $sortDirection = 'desc';
        $this->createGraphQLOperation()->sort($sortField, $sortDirection);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertIsArray($operationParameters);
        $this->assertSame(['type' => 'object', 'value' => '[{field:"' . $sortField . '",direction:' . $sortDirection . '}]'], $operationParameters['argument']['sort_by']);
    }

    public function testExecute()
    {
        $apiResponse = new ApiResponse(json_encode((object)['data' => true]));

        $this->createGraphQLService(['methods' => ['assembleAndDispatchGraphQLCall' => $apiResponse], 'partialMock' => true]);

        $this->createGraphQLOperation();

        $this->graphQLOperation->locale('en')->properties(['id']);
        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertNotEmpty($operationParameters['argument']);
        $this->assertNotEmpty($operationParameters['property']);

        $result = $this->graphQLOperation->execute();
        $this->assertSame($apiResponse, $result);

        $operationParameters = $this->accessProtectedProperty($this->graphQLOperation, 'operationParameters');
        $this->assertEmpty($operationParameters['argument']);
        $this->assertEmpty($operationParameters['property']);
    }

}
