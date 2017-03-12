<?php
namespace integrityChecker;

class RestTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
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

        $settings = new \MockSettings();
        $apiClient = new \MockApiClient(array());
        $process = new \MockProcess();
        $fileDiff = new \MockFileDiff();
        $rest = new Rest($settings, $apiClient, $process, $fileDiff);
        $rest->registerRestEndpoints();

        $this->assertTrue(isset($mockRestEndpoints['integrity-checker/v1']));
        $v1 = $mockRestEndpoints['integrity-checker/v1'];

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
        );

        foreach ($endpoints as $name => $checks) {
            $this->assertTrue(isset($v1[$name]));
            $endpoint = $v1[$name];
            foreach ($checks['methods'] as $method) {
                $this->assertTrue(isset($endpoint[$method]), "$name $method");
                $this->assertTrue(isset($endpoint[$method]['callback']));
            }
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
}