<?php
namespace integrityChecker;

class AdminUIHooksTest extends \PHPUnit_Framework_TestCase
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
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
    }
}