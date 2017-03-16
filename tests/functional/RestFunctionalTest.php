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
            $ret = $this->restGet($testUrl . '/wp-json/integrity-checker/v1/testresult/' . $testName);

            $this->assertTrue(isset($ret['response']));
            $response = $ret['response'];
            $this->assertEquals(200, $response['code']);
            $this->assertTrue(isset($ret['body']));
            $body = $ret['body'];
            $this->assertEquals('success', $body->code);
        }
    }

    public function testRunTest()
    {
        global $testUrl;

        $tests = array('checksum', 'settings');
        //$tests = array('settings');

        foreach ($tests as $testName) {
            $ret = $this->restPost(
                $testUrl . '/wp-json/integrity-checker/v1/process/status/' . $testName,
                array(
                    'headers' => array(
                        'X-HTTP-Method-Override: PUT'
                    ),
                    'body' => json_encode((object)array('state' => 'started')),
                )
            );

            $this->assertEquals(200, $ret['response']['code']);
            $body = $ret['body'];
            // since v 0.10, tests are always run in a separate
            // request. So this test can't be finished yet
            $this->assertEquals('started', $body->data->state);

            // ...so we have to wait for the test to finish
            $i = 0;
            while (true) {
                $i++;
                $ret = $this->restGet($testUrl . '/wp-json/integrity-checker/v1/process/status/' . $testName);
                $body = $ret['body'];
                if ($body->data->state == 'finished') {
                    break;
                }
                if ($i > 10) {
                    break;
                }
                sleep(1);
            }

            // Get the results
            $ret = $this->restGet($testUrl . '/wp-json/integrity-checker/v1/testresult/' . $testName);
            $body = $ret['body'];

            switch ($testName) {
                case 'checksum':
                    $this->assertTrue(isset($body->data->core));
                    $this->assertTrue(isset($body->data->core->core));
                    $this->assertTrue(isset($body->data->core->core->name));
                    $this->assertEquals('Core', $body->data->core->core->name);
                    $this->assertEquals('core', $body->data->core->core->slug);
                    $this->assertEquals('checked', $body->data->core->core->status);

                    $this->assertTrue(isset($body->data->plugins));
                    $this->assertTrue(isset($body->data->plugins->akismet));
                    $this->assertTrue(isset($body->data->plugins->akismet->name));
                    $this->assertEquals('Akismet Anti-Spam', $body->data->plugins->akismet->name);
                    $this->assertEquals('akismet', $body->data->plugins->akismet->slug);
                    $this->assertEquals('checked', $body->data->plugins->akismet->status);

                    $this->assertTrue(isset($body->data->themes));
                    $this->assertTrue(isset($body->data->themes->twentysixteen));
                    $this->assertTrue(isset($body->data->themes->twentysixteen->name));
                    $this->assertEquals('Twenty Sixteen', $body->data->themes->twentysixteen->name);
                    break;

                case 'settings':
                    $this->assertTrue(isset($body->data->checks));
                    $this->assertTrue(isset($body->data->checks->allowFileEdit));
                    $this->assertTrue(isset($body->data->checks->dbCredentials));
                    $this->assertTrue(isset($body->data->checks->allowFileEdit));
                    $this->assertTrue(isset($body->data->checks->sslLogins));
                    $this->assertTrue(isset($body->data->checks->checkUpdates));
                    $this->assertTrue(isset($body->data->checks->directoryIndex));
                    $this->assertTrue(isset($body->data->checks->checkSalts));
                    $this->assertTrue(isset($body->data->checks->userEnumeration));
                    $this->assertTrue(isset($body->data->checks->versionLeak));
                    $this->assertTrue(isset($body->data->checks->checkTablePrefix));
                    $this->assertTrue(isset($body->data->checks->adminUsername));
                    break;
            }
        }
    }

    /***********************************************************/

    private function restPost($url, $args)
    {
        $opts = array('http' =>
                          array(
                              'method'  => 'PUT',
                              'header'  => 'Content-type: application/json',
                              'content' => isset($args['body'])? $args['body'] : null,
                          )
        );

        if (isset($args['headers'])) {
            foreach ($args['headers'] as $header) {
                $opts['http']['header'] .= "\n" . $header;
            }
        }

        $context  = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);
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