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
        $ic = new integrityChecker(
            (object)array('slug' => 'foobar'),
            (object)array(),
            (object)array(),
            (object)array(),
            (object)array()
        );

        $this->assertEquals('foobar', $ic->getPluginSlug());
        $this->assertEquals('0.9.3', $ic->getVersion());
    }

    public function _testInit()
    {
        /*
        \WP_Mock::wpFunction(
            'plugin_basename',
            array(
                'return' => 'foobar'
            )
        );
        $this->assertEquals('foobar', $ic->getPluginBaseName());

        $ic2 = integrityChecker::getInstance();
        $this->assertTrue($ic === $ic2);
        $this->assertTrue(is_array($ic->getTestNames()));
        $this->assertTrue(count($ic->getTestNames()) > 3)

        \WP_Mock::wpFunction(
            'get_option',
            array(
                'return' => 'foobar'
            )
        );

        $ic = integrityChecker::getInstance();
        $ic->init();*/


    }
}
