<?php
namespace integrityChecker;

class RestTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
        require_once INTEGRITY_CHECKER_ROOT . '/tests/class-wp-error.php';
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstruct()
    {
        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();
        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
    }

    public function testRegisterRestEndpoints()
    {
        global $mockRestEndpoints;
        $mockRestEndpoints = array();

        \WP_Mock::userFunction('register_rest_route', array(
            'return'=> function($base, $endPoint, $args) {
                global $mockRestEndpoints;
                $mockRestEndpoints[] = array($base, $endPoint, $args);
            }
        ));

        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();
        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $rest->registerRestEndpoints();

        $endpoints = array(
            'quota' => array('methods' => array('GET'), 'secure' => 1),
            'apikey' => array('methods' => array('PUT'), 'secure' => 1),
            'userdata' => array('methods' => array('PUT'), 'secure' => 1),
            'process/status' => array('methods' => array('GET'), 'secure' => 1),
            'process/status/(?P<name>[a-zA-Z0-9-]+)' => array('methods' => array('GET', 'PUT'), 'secure' => 1),
            'testresult/(?P<name>[a-zA-Z0-9-]+)' => array('methods' => array('GET'), 'secure' => 1),
            'diff/(?P<type>[a-zA-Z0-9-]+)/(?P<slug>[a-zA-Z0-9-]+)' => array('methods' => array('GET'), 'secure' => 1),
            'testemail/(?P<emails>.*)' => array('methods' => array('GET'), 'secure' => 1),
            'settings' => array('methods' => array('PUT'), 'secure' => 1),
            'testresult/scanall/truncatehistory' => array('methods' => array('PUT'), 'secure' => 1),
        );

        foreach ($mockRestEndpoints as $mockedEndpoint) {
            $this->assertTrue(isset($endpoints[$mockedEndpoint[1]]));
            $endpoint = $endpoints[$mockedEndpoint[1]];
            $this->assertEquals('integrity-checker/v1', $mockedEndpoint[0]);
            $this->assertTrue(in_array($mockedEndpoint[2]['methods'][0], $endpoint['methods']));
        }

        $mockRestEndpoints = array();
    }

    public function testCheckPermissions()
    {
        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();

        $nonce = 'foononce';

        \WP_Mock::userFunction('wp_verify_nonce', array(
            'args' => array($nonce, 'wp_rest'),
            'return_in_order' => array(false, true),
            'times' => 2,
        ));


        $request = new \MockRequest(array('headers' => array('X-WP-NONCE' => $nonce)));
        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $ret = $rest->checkPermissions($request);
        $this->assertFalse($ret);
        $ret = $rest->checkPermissions($request);
        $this->assertTrue($ret);
    }

    public function testErrSend()
    {
        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();

        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $ret =$rest->errSend(new \WP_Error('CODE', 'MESSAGE', 'DATA'));

        $this->assertEquals('MESSAGE', $ret->errors['CODE'][0]);
        $this->assertEquals('CODE', $ret->error_data['CODE']['status']);
        $this->assertEquals('MESSAGE', $ret->error_data['CODE']['message']);

        $ret =$rest->errSend(new \WP_Error('CODE', 'MESSAGE', array('status' => 'DATA')));
        $this->assertTrue($ret instanceof \WP_Error);
    }

    public function testJSend()
    {
        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();

        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $ret =$rest->jSend((object)array('foo' => 'bar'));
        $this->assertEquals('success', $ret->code);
        $this->assertEquals(null, $ret->message);
        $this->assertEquals('bar', $ret->data->foo);
    }

    public function testEscapeObjectString()
    {
        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();

        \WP_Mock::userFunction('esc_html', array(
            'return'=> function($str) {
                return htmlspecialchars($str);
            }
        ));

        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $message = (object)array(
            'prop1' => 'räksmörgås',
            'prop2' => array('elem1' => '<html>'),
            'prop3' => (object)array('elem1' => '<html>'),
        );
        $rest->escapeObjectStrings($message);
        $this->assertEquals('räksmörgås', $message->prop1);
        $this->assertEquals('&lt;html&gt;', $message->prop2['elem1']);
        $this->assertEquals('&lt;html&gt;', $message->prop3->elem1);
        $message = false;
        $rest->escapeObjectStrings($message);
        $this->assertEquals(false, $message);

        $message = null;
        $rest->escapeObjectStrings($message);
        $this->assertEquals(null, $message);

    }
}