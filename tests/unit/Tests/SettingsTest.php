<?php

namespace integrityChecker;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
        \WP_Mock::wpPassthruFunction('__', 'a');
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testStart()
    {
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->start(new \MockRequest());
    }

    public function testShapeResult()
    {
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->shapeResult(new \MockRequest());
    }

    public function testCheckTablePrefix()
    {
        global $table_prefix;

        $table_prefix = 'wp_';
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->checkTablePrefix($dummy);
        $t = $s->transientState;
        $this->assertFalse($t['checkTablePrefix']['acceptable']);

        $table_prefix = 'somethingelse_';
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->checkTablePrefix($dummy);
        $t = $s->transientState;
        $this->assertTrue($t['checkTablePrefix']['acceptable']);
    }

    public function testAdminUsername()
    {
        \WP_Mock::userFunction('username_exists', array(
            'args' => 'admin',
            'return' => false,
            'times' => 1,
        ));
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->adminUsername($dummy);
        $t = $s->transientState;
        print_r($t);
        $this->assertTrue($t['adminUsername']['acceptable']);
        $this->assertTrue(stripos($t['adminUsername']['result'], "does not exist") !==false);


        \WP_Mock::userFunction('username_exists', array(
            'args' => 'admin',
            'return' => true,
            'times' => 1,
        ));
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->adminUsername($dummy);
        $t = $s->transientState;
        print_r($t);
        $this->assertFalse($t['adminUsername']['acceptable']);
        $this->assertTrue(stripos($t['adminUsername']['result'], "Exists") !==false);

    }

}