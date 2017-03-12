<?php
namespace integrityChecker;

class RuntimeProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
        require_once dirname(dirname(__DIR__)) . '/src/RuntimeProvider.php';
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstruct()
    {
        $provider = new \RuntimeProvider();
    }
}