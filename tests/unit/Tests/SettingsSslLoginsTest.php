<?php

namespace integrityChecker;

class SettingsSslLoginsTest extends \PHPUnit_Framework_TestCase
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
    public function testSslLogins1()
    {
        define('FORCE_SSL_ADMIN', true);

        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->sslLogins($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['sslLogins']));
        $this->assertEquals(true, $t['sslLogins']['acceptable']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSslLogins2()
    {
        define('FORCE_SSL_ADMIN', false);

        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->sslLogins($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['sslLogins']));
        $this->assertEquals(false, $t['sslLogins']['acceptable']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSslLogins3()
    {
        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->sslLogins($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['sslLogins']));
        $this->assertEquals(false, $t['sslLogins']['acceptable']);
    }
}