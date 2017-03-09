<?php

namespace integrityChecker;

class SettingsAllowFileEditTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
        \WP_Mock::userFunction('__', array());
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    /**
     * @runInSeparateProcess
     */
    public function testAllowFileEdit()
    {
        define('DISALLOW_FILE_EDIT', true);

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->allowFileEdit($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['allowFileEdit']));
        $this->assertEquals(true, $t['allowFileEdit']['acceptable']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAllowFileEdit2()
    {
        define('DISALLOW_FILE_EDIT', false);

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->allowFileEdit($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['allowFileEdit']));
        $this->assertEquals(false, $t['allowFileEdit']['acceptable']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAllowFileEdit3()
    {
        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->allowFileEdit($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['allowFileEdit']));
        $this->assertEquals(false, $t['allowFileEdit']['acceptable']);
    }


}