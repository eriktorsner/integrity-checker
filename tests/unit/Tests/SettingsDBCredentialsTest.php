<?php

namespace integrityChecker;

class SettingsDBCredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    /**
     * @runInSeparateProcess
     */
    public function testdbCredentialsPasswordWeak()
    {
        define('DB_PASSWORD', 'user');
        define('DB_USER', 'user');
        define('DB_NAME', 'wordpress');
        define('DB_HOST', 'localhost');

        \WP_Mock::userFunction('get_option');

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->dbCredentials($dummy);
        $t = $s->transientState;

        $this->assertTrue(isset($t['dbCredentials']));
        $dbc = $t['dbCredentials'];
        $this->assertEquals($dbc['type'], 'severe');
        $this->assertTrue(strpos($dbc['result'], 'DB password easy to guess') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB password is shorter than 10 characters') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB password does not contain numbers') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB password does not contain capitalized letters') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB password does not contain symbols') !== false);
    }

    /**
     * @runInSeparateProcess
     */
    public function testdbCredentialsPasswordWeak2()
    {

        define('DB_PASSWORD', 'USER');
        define('DB_USER', 'admin');
        define('DB_NAME', 'wordpress');
        define('DB_HOST', 'localhost');

        \WP_Mock::userFunction('get_option');

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->dbCredentials($dummy);
        $t = $s->transientState;

        $this->assertTrue(isset($t['dbCredentials']));
        $dbc = $t['dbCredentials'];
        $this->assertEquals($dbc['type'], 'severe');
        $this->assertTrue(strpos($dbc['result'], 'DB password does not contain lowercase letters') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB user is too easy to guess') !== false);
    }

    /**
     * @runInSeparateProcess
     */
    public function testdbCredentialsPasswordWeak3()
    {
        define('DB_PASSWORD', 'USER');
        define('DB_USER', 'admin');
        define('DB_NAME', 'USER');
        define('DB_HOST', 'localhost');

        \WP_Mock::userFunction('get_option');

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->dbCredentials($dummy);
        $t = $s->transientState;

        $this->assertTrue(isset($t['dbCredentials']));
        $dbc = $t['dbCredentials'];
        $this->assertEquals($dbc['type'], 'severe');
        $this->assertTrue(strpos($dbc['result'], 'DB password does not contain lowercase letters') !== false);
        $this->assertTrue(strpos($dbc['result'], 'DB password is far too easy to guess') !== false);
    }

}