<?php

class RestFunctionalTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        setUpWp();
    }

    public function testQuota()
    {
        global $testUrl;

        $ret = $this->restGet(
            $testUrl . '/wp-json/integrity-checker/v1/quota'
        );

        echo "\n";
        echo $testUrl . '/wp-json/integrity-checker/v1/quota' . "\n";
        print_r($ret);

        $this->assertTrue(isset($ret['body']));
        $body = $ret['body'];
        $this->assertTrue(isset($body->code));
        $this->assertEquals('success', $body->code);
        $this->assertTrue(isset($body->data));
        $this->assertEquals('ANONYMOUS', $body->data->validationStatus);

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