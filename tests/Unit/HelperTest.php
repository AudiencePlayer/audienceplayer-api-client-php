<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Resources\Helper;
use Tests\TestCase;

class HelperTest extends TestCase
{
    public function testEscapeString()
    {
        $helper = new Helper();
        $this->assertSame('foo \" bar \"', $helper->escapeString('foo " bar "'));
        $this->assertSame('bar', $helper->escapeString('foo', ['foo'], ['bar']));
        $this->assertSame('foo2 \" bar2 \"', $helper->escapeString('foo1 " bar1 "', ['"', 1], ['\"', 2]));
    }

    public function testHasArrayOnlyIntegers()
    {
        $helper = new Helper();

        $this->assertFalse($helper->hasArrayOnlyIntegers([]));
        $this->assertFalse($helper->hasArrayOnlyIntegers([null]));
        $this->assertFalse($helper->hasArrayOnlyIntegers([false]));
        $this->assertFalse($helper->hasArrayOnlyIntegers([1, 2, 'a']));

        $this->assertTrue($helper->hasArrayOnlyIntegers([0]));
        $this->assertTrue($helper->hasArrayOnlyIntegers([1]));
        $this->assertTrue($helper->hasArrayOnlyIntegers([1, 2, 3]));
        $this->assertTrue($helper->hasArrayOnlyIntegers(['a' => 1, 2, 'c' => 3]));
    }

    public function testPrepareCurlRequest()
    {
        $helper = new Helper();
        //$this->fetchClassMock(Helper::class, ['execCurl' => '<CURL_RESULT>'], [], true);

        $url = 'https://example.org';
        $forwardForIp = '123.123.123.123';

        $options = [
            'CURLOPT_HEADER' => true,
            'CURLOPT_HTTPHEADER' => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer bearerToken',
            ],
            'CURLOPT_POSTFIELDS' => json_encode([
                'query' => 'query{}',
                'operationName' => 'operationName',
                'variables' => ['id' => 1],
            ]),
            'CURLOPT_USERAGENT' => 'useragent',
            'CURLOPT_USERPWD' => 'userpassword',
            'CURLOPT_CUSTOMREQUEST' => 'foobar',

        ];

        $result = $this->accessProtectedMethod($helper, 'prepareCurlRequest', [$url, $options, $forwardForIp]);

        $this->assertEquals($url, $result[CURLOPT_URL]);
        $this->assertEquals($options['CURLOPT_HEADER'], $result[CURLOPT_HEADER]);
        $this->assertEquals(array_merge($options['CURLOPT_HTTPHEADER'], ['X-Forwarded-For: ' . $forwardForIp]), $result[CURLOPT_HTTPHEADER]);
        $this->assertEquals(false, $result[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals(true, $result[CURLOPT_RETURNTRANSFER]);
        $this->assertEquals(30, $result[CURLOPT_CONNECTTIMEOUT]);
        $this->assertEquals(60, $result[CURLOPT_TIMEOUT]);
        $this->assertEquals($options['CURLOPT_USERAGENT'], $result[CURLOPT_USERAGENT]);
        $this->assertEquals($options['CURLOPT_USERPWD'], $result[CURLOPT_USERPWD]);
        $this->assertEquals($options['CURLOPT_CUSTOMREQUEST'], $result[CURLOPT_CUSTOMREQUEST]);
        $this->assertEquals(1, $result[CURLOPT_POST]);
        $this->assertEquals($options['CURLOPT_POSTFIELDS'], $result[CURLOPT_POSTFIELDS]);
    }

    /**
     * @dataProvider parseSingleGraphQLArgumentDataProvider
     * @param $key
     * @param $value
     * @param $expectedResult
     */
    public function testParseSingleGraphQLArgument($key, $value, $expectedResult)
    {
        $helper = new Helper();
        $this->assertEquals($expectedResult, $helper->parseSingleGraphQLArgument($value, $key));
    }

    public function parseSingleGraphQLArgumentDataProvider(): array
    {
        // $key, $value, $expectedResult
        return [
            ['', '', '""'],
            ['', [], '[]'],
            ['lorem', [], 'lorem:[]'],
            ['', 1, '1'],
            ['lorem', 1, 'lorem:1'],
            ['lorem', 7.99, 'lorem:7.99'],
            ['lorem', 'ipsum', 'lorem:"ipsum"'],
            ['lorem', [1, 2], 'lorem:[1,2]'],
            ['lorem', ['value' => 'ipsum', 'type' => 'string'], 'lorem:"ipsum"'],
            ['lorem', ['value' => 'ipsum', 'type' => 'enum'], 'lorem:ipsum'],
            ['lorem', ['value' => 'ipsum', 'type' => 'bool'], 'lorem:true'],
            ['lorem', ['value' => 'ipsum', 'type' => 'boolean'], 'lorem:true'],
            ['lorem', ['value' => 'true', 'type' => 'boolean'], 'lorem:true'],
            ['lorem', ['value' => '1', 'type' => 'boolean'], 'lorem:true'],
            ['lorem', ['value' => 1, 'type' => 'boolean'], 'lorem:true'],
            ['lorem', ['value' => 'false', 'type' => 'boolean'], 'lorem:false'],
            ['lorem', ['value' => ' 0 ', 'type' => 'boolean'], 'lorem:false'],
            ['lorem', ['value' => 0, 'type' => 'boolean'], 'lorem:false'],
            ['lorem', ['value' => '', 'type' => 'boolean'], 'lorem:false'],
            ['lorem', ['value' => 'ipsum', 'type' => 'null'], 'lorem:null'],
            ['lorem', ['value' => '[1,2,3]', 'type' => 'array'], 'lorem:[1,2,3]'],
            ['lorem', ['value' => [1, 2, 3], 'type' => 'array'], 'lorem:[1,2,3]'],
            ['lorem', ['value' => [1, '2', 3], 'type' => 'array'], 'lorem:[1,"2",3]'],
            ['lorem', [['value' => [1, 3], 'type' => 'array'], 'bar', ['value' => 'web', 'type' => 'enum']], 'lorem:[[1,3],"bar",web]'],
            ['lorem', ['value' => '[web,mobile]', 'type' => 'enum'], 'lorem:[web,mobile]'],
        ];
    }

}


