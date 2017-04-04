<?php

namespace integrityChecker;

class SettingsTestTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
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
        $this->assertFalse($t['adminUsername']['acceptable']);
        $this->assertTrue(stripos($t['adminUsername']['result'], "Exists") !==false);

    }

    public function testVersionLeak()
    {
        global $wp_version;

        @unlink(ABSPATH . '/readme.html');

        \WP_Mock::userFunction('get_the_generator', array(
            'args' => 'html',
            'return' => 'generated 9.9.9',
            'times' => 3,
        ));

        $wp_version = '9.9.9';
        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->versionLeak($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['versionLeak']));
        $this->assertEquals(false, $t['versionLeak']['acceptable']);
        $this->assertTrue(stripos($t['versionLeak']['result'], 'The generator tag') !== false);


        $wp_version = '10.1';
        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->versionLeak($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['versionLeak']));
        $this->assertEquals(true, $t['versionLeak']['acceptable']);
        $this->assertTrue(stripos($t['versionLeak']['result'], 'You are not showing') !== false);

        $wp_version = '10.1';
        file_put_contents(ABSPATH . '/readme.html', 'foobar');
        $dummy = new \stdClass();
        $s     = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->versionLeak($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['versionLeak']));
        $this->assertEquals(false, $t['versionLeak']['acceptable']);
        $this->assertTrue(stripos($t['versionLeak']['result'], 'readme.html file exists') !== false);
    }

    public function testCheckUpdates()
    {
        \WP_Mock::userFunction('get_users', array(
            'return' => array((object)array('data' => (object)array(
                'ID' => 99, 'user_login' => 'foo')
            )),
            'times' => 2,
        ));
        \WP_Mock::userFunction('wp_set_current_user', array('return' => null));
        \WP_Mock::userFunction('wp_get_update_data', array(
            'return' => array('counts' => array('total' => 1)),
            'times' => 1,
        ));
        @mkdir(ABSPATH . '/wp-admin/includes', 0777, true);
        file_put_contents(ABSPATH . '/wp-admin/includes/update.php', '<?php');
        $dummy = new \stdClass();

        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->checkUpdates($dummy);
        $t = $s->transientState;
        $this->assertEquals(false, $t['checkUpdates']['acceptable']);

        \WP_Mock::userFunction('wp_get_update_data', array(
            'return' => array('counts' => array('total' => 0)),
            'times' => 1,
        ));
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->checkUpdates($dummy);
        $t = $s->transientState;
        $this->assertEquals(true, $t['checkUpdates']['acceptable']);

        @unlink(ABSPATH . '/wp-admin/includes/update.php');

    }

    public function testDirectoryIndex()
    {
        \WP_Mock::userFunction('plugins_url', array('return' => 'http://foobar'));

        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                null,
                array(
                    'body' => 'dasdad asdsad integrity-checker.php asdsad integrity-checker.php',
                ),
            ),
        ));


        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->directoryIndex($dummy);
        $t = $s->transientState;
        $this->assertEquals(true, $t['directoryIndex']['acceptable']);

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->directoryIndex($dummy);
        $t = $s->transientState;
        $this->assertEquals(false, $t['directoryIndex']['acceptable']);
    }

    public function testUserEnumeration()
    {
        \WP_Mock::userFunction('get_users', array(
            'return' => array((object)array(
                'ID' => 99, 'user_login' => 'foo',
            )),
            'times' => 2,
        ));
        \WP_Mock::userFunction('get_home_url', array('return' => 'http://foobar'));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                array(
                    'body' => 'nada',
                ),
                array(
                    'body' => 's s  author-foo s s author-99 sad asd',
                ),
            ),
            'times' => 2,
        ));
        \WP_Mock::userFunction('is_wp_error', array(
            'return_in_order' => array(
                false,
            )
        ));



        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->userEnumeration($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['userEnumeration']));
        $this->assertEquals(true, $t['userEnumeration']['acceptable']);

        $dummy = new \stdClass();
        $s = new Tests\Settings($dummy, $dummy, $dummy, $dummy);
        $s->userEnumeration($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['userEnumeration']));
        $this->assertEquals(false, $t['userEnumeration']['acceptable']);

    }

}