<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse;
use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    /**
     * @dataProvider parseDataDataProvider
     * @param $rawInputResult
     * @param $operationName
     * @param $expectedParsedInputResult
     * @param $expectedResult
     * @throws \ReflectionException
     * @covers       \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::parseData
     */
    public function testParseData($rawInputResult, $operationName, $expectedParsedInputResult, $expectedResult)
    {
        $apiResponse = new ApiResponse($rawInputResult, $operationName);

        $this->assertSame($rawInputResult, $this->accessProtectedProperty($apiResponse, 'rawInputResult'));
        $this->assertEquals($expectedParsedInputResult, $this->accessProtectedProperty($apiResponse, 'parsedInputResult'));
        $this->assertSame($expectedResult, $this->accessProtectedProperty($apiResponse, 'parsingStatus'));
    }

    public function parseDataDataProvider(): array
    {
        // $rawInputResult, $operationName, $expectedParsedInputResult, $expectedResult
        return [
            [
                '{"data":"foobar"}',
                'UserDetails',
                json_decode('{"data":"foobar"}'),
                true,
            ],
            [
                '{"data":"foobar","errors":[{"code":123,"message":"foobar","operation":"UserDetails"}]}',
                'UserDetails',
                json_decode('{"data":"foobar","errors":[{"code":123,"message":"foobar","operation":"UserDetails"}]}'),
                true,
            ],
            [
                '{{bad-json',
                'UserDetails',
                ApiResponse::assembleDefaultErrorResponse(
                    'UserDetails',
                    Globals::MSG_API_RESPONSE_PARSE_ERROR,
                    Globals::STATUS_API_RESPONSE_PARSE_ERROR
                ),
                false,
            ],
            [
                '{"unexpected":"json"}',
                'UserDetails',
                ApiResponse::assembleDefaultErrorResponse(
                    'UserDetails',
                    Globals::MSG_API_RESPONSE_FORMAT_ERROR,
                    Globals::STATUS_API_RESPONSE_FORMAT_ERROR
                ),
                false,
            ],
        ];
    }

    /**
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getData
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getRawData
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getParsedData
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getOperationName
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getOperationQuery
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getOperationVariables
     */
    public function testGetMethods()
    {
        $apiResponse = new ApiResponse(['data' => 'foobar'], 'UserDetails');
        $this->assertClassGetMethod($apiResponse, 'data', 'foobar', 'foobar', 'foobar', 'getData');
        $this->assertClassGetMethod($apiResponse, 'rawInputResult', ['data' => 'foobar'], 'foobar', 'foobar', 'getRawData');
        $this->assertClassGetMethod($apiResponse, 'parsedInputResult', (object)['data' => 'foobar'], 'foobar', 'foobar', 'getParsedData');
        $this->assertClassGetMethod($apiResponse, 'parsingStatus', true, false, false, 'isDataParsed');
        $this->assertClassGetMethod($apiResponse, 'operationName', 'UserDetails', 'foobar', 'foobar', 'getOperationName');
        $this->assertClassGetMethod($apiResponse, 'operationQuery', '', 'foobar', 'foobar', 'getOperationQuery');
        $this->assertClassGetMethod($apiResponse, 'operationVariables', [], ['foo'], ['foo'], 'getOperationVariables');
    }

    /**
     * @param $hasErrors
     * @param $propertyValue
     * @param $expectedValue
     * @param $expectedFirstErrorCode
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::hasErrors
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getErrors
     * @covers \AudiencePlayer\AudiencePlayerApiClient\Resources\ApiResponse::getFirstErrorCode
     * @testWith    [false, "[]", "[]", null]
     *              [true, "[0]", "[0]", null]
     *              [true, "[1,2,3]", "[1,2,3]", null]
     *              [true, "[{\"code\":123}]", "[{\"code\":123}]", 123]
     */
    public function testErrorMethods($hasErrors, $propertyValue, $expectedValue, $expectedFirstErrorCode)
    {
        $apiResponse = new ApiResponse(['data' => true], 'UserDetails');

        $this->assertFalse($apiResponse->hasErrors());
        $this->assertSame([], $apiResponse->getErrors());
        $this->assertNull($apiResponse->getFirstErrorCode());

        $this->setProtectedProperty($apiResponse, 'errors', json_decode($propertyValue));
        $this->assertSame($hasErrors, $apiResponse->hasErrors());
        $this->assertEquals(json_decode($expectedValue), $apiResponse->getErrors());
        $this->assertSame($expectedFirstErrorCode, $apiResponse->getFirstErrorCode());
    }

    /**
     * @param $hasErrors
     * @param $parsingStatus
     * @param $expectedResult
     * @testWith    [true, true, false]
     *              [true, false, false]
     *              [false, true, true]
     *              [false, false, false]
     */
    public function testIsSuccessful($hasErrors, $parsingStatus, $expectedResult)
    {
        $apiResponse = new ApiResponse(['data' => true], 'UserDetails');
        $this->setProtectedProperty($apiResponse, 'errors', $hasErrors ? [1, 2, 3] : []);
        $this->setProtectedProperty($apiResponse, 'parsingStatus', $parsingStatus);
        $this->assertSame($expectedResult, $apiResponse->isSuccessful());
    }

    public function testAssembleDefaultErrorResponse()
    {
        $this->assertEquals(
            (object)['data' => null, 'errors' => [(object)['operation' => 'foobar', 'message' => 'lorem ipsum', 'code' => Globals::STATUS_GENERAL_ERROR]]],
            ApiResponse::assembleDefaultErrorResponse('foobar', 'lorem ipsum')
        );

        $this->assertEquals(
            (object)['data' => null, 'errors' => [(object)['operation' => 'foobar', 'message' => 'lorem ipsum', 'code' => 123]]],
            ApiResponse::assembleDefaultErrorResponse('foobar', 'lorem ipsum', 123)
        );
    }

}
