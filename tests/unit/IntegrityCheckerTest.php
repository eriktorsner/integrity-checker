<?php
namespace integrityChecker;

class IntegrityCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstructor()
    {
        $ic = integrityChecker::getInstance();
        $this->assertEquals('integrity-checker', $ic->getPluginSlug());
        $this->assertEquals('0.9.3', $ic->getVersion());

        \WP_Mock::wpFunction(
            'plugin_basename',
            array(
                'return' => 'foobar'
            )
        );

        $this->assertEquals('foobar', $ic->getPluginBaseName());
    }
}