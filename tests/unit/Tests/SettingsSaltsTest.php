<?php

namespace integrityChecker;

class SettingsSaltsTest extends \PHPUnit_Framework_TestCase
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
    public function testdbAllSaltsBad()
    {
        $salts = array(
            'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
        );
        foreach ($salts as $salt) {
            if (!defined($salt)) {
                define($salt, 'areallybadsalt');
            }
        }


        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->checkSalts($dummy);
        $t = $s->transientState;

        $this->assertTrue(isset($t['checkSalts']));
        $salts = $t['checkSalts'];
        $this->assertEquals(false, $salts['acceptable']);
        $this->assertTrue(strpos($salts['result'], 'exists but have too low entropy') !== false);
    }

    /**
     * @runInSeparateProcess
     */
    public function testdbAllSaltsGood()
    {
        $salts = array(
            'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
        );
        foreach ($salts as $salt) {
            if (!defined($salt)) {
                define($salt, 'h T/HR6HE{-wV-a!>$_5k,6;&L.Q-JGP;6mDU9vJ:{iD9qBo+hb}O)#]OkPP`Mei');
            }
        }

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->checkSalts($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['checkSalts']));
        $salts = $t['checkSalts'];
        $this->assertEquals(true, $salts['acceptable']);
        $this->assertFalse(strpos($salts['result'], 'exists but have too low entropy') !== false);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSaltMissing()
    {
        $salts = array(
            'AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'NONCE_SALT',
        );
        foreach ($salts as $salt) {
            if (!defined($salt)) {
                define($salt, 'h T/HR6HE{-wV-a!>$_5k,6;&L.Q-JGP;6mDU9vJ:{iD9qBo+hb}O)#]OkPP`Mei');
            }
        }

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->checkSalts($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['checkSalts']));
        $salts = $t['checkSalts'];
        $this->assertEquals(false, $salts['acceptable']);
        $this->assertTrue(strpos($salts['result'], "isn't defined") !== false);
    }

}