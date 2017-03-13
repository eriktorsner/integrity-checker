<?php

class RestFunctionalTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        setUpWp();
    }

    public function setUp()
    {
        $this->tests = array(
            'checksum',
            'scanall',
            'files',
            'settings',
        );
    }

    public function testQuota()
    {
        global $testUrl;

        $ret = $this->restGet(
            $testUrl . '/wp-json/integrity-checker/v1/quota'
        );

        $this->assertTrue(isset($ret['body']));
        $body = $ret['body'];
        $this->assertTrue(isset($body->code));
        $this->assertEquals('success', $body->code);
        $this->assertTrue(isset($body->data));
        $this->assertEquals('ANONYMOUS', $body->data->validationStatus);

    }

    public function testGetProcessStatus()
    {
        global $testUrl;

        $ret = $this->restGet(
            $testUrl . '/wp-json/integrity-checker/v1/process/status'
        );

        $this->assertTrue(isset($ret['body']));
        $body = $ret['body'];
        $this->assertTrue(isset($body->code));
        $this->assertEquals('success', $body->code);
        $this->assertTrue(isset($body->data));

        foreach ($this->tests as $testName) {
            $this->assertTrue(isset($body->data->$testName));
            $this->assertEquals('never_started', $body->data->$testName->state);
        }

        foreach ($this->tests as $testName) {
            $ret = $this->restGet(
                $testUrl . '/wp-json/integrity-checker/v1/process/status/' . $testName
            );
            $body = $ret['body'];
            $this->assertEquals('success', $body->code);
            $this->assertTrue(isset($body->data));
            $this->assertEquals('never_started', $body->data->state);
        }
    }

    public function testGetTestResults()
    {
        global $testUrl;

        foreach ($this->tests as $testName) {
            $ret = $this->restGet(
                $testUrl . '/wp-json/integrity-checker/v1/testresult/' . $testName
            );

            $this->assertTrue(isset($ret['response']));
            $response = $ret['response'];
            $this->assertEquals(200, $response['code']);
            $this->assertTrue(isset($ret['body']));
            $body = $ret['body'];
            $this->assertEquals('success', $body->code);
        }
    }

    private function remotePost($url, $args)
    {
        $opts = array('http' =>
                          array(
                              'method'  => 'POST',
                              'header'  => 'Content-type: application/x-www-form-urlencoded',
                              'content' => isset($args['body'])? $args['body'] : null,
                          )
        );

        $context  = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);
        $codeParts = explode(' ', $http_response_header[0]);

        return array(
            'response' => array(
                'code' => $codeParts[1],
            ),
            'body' => $content,
        );
    }

    private function restGet($url, $args = null)
    {
        $content = @file_get_contents($url);
        $codeParts = explode(' ', $http_response_header[0]);

        if (json_decode($content)) {
            $content = json_decode($content);
        }

        return array(
            'response' => array(
                'code' => $codeParts[1],
            ),
            'body' => $content,
        );
    }
}