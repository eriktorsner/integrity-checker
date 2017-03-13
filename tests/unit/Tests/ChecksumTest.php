<?php

namespace integrityChecker;

class ChecksumTest extends \PHPUnit_Framework_TestCase
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
        $c = new Tests\Checksum($dummy, new \MockState(), $dummy, $dummy);
        $c->setBackgroundProcess(new \MockBackgroundProcess());
        $c->start(new \MockRequest());
    }

    public function testAnalyze()
    {
        $dummy = new \stdClass();
        $c = new Tests\Checksum($dummy, new \MockState(), $dummy, $dummy);
        $c->transientState = array(
            'core' => array(
                array(
                    'name' => 'core',
                    'slug' => 'core',
                    'version' => '99.9.1',
                    'status' => 'STATUS1',
                    'message' => 'MESSAGE',
                    'error' => ApiClient::NO_APIKEY,
                    'changeset' => array(),
                ),
            ),
            'plugins' => array(
                array(
                    'name' => 'A common plugin',
                    'slug' => 'common-plugin',
                    'version' => '1.2.3',
                    'status' => 'STATUS2',
                    'message' => 'MESSAGE',
                    'error' => ApiClient::INVALID_APIKEY,
                    'changeset' => array(),
                ),
                array(
                    'name' => 'Another plugin',
                    'slug' => 'another-plugin',
                    'version' => '3.2.1',
                    'status' => 'STATUS3',
                    'message' => 'MESSAGE',
                    'error' => ApiClient::RATE_LIMIT_EXCEEDED,
                    'changeset' => array(),
                ),
                array(
                    'name' => 'Another plugin2',
                    'slug' => 'another-plugin2',
                    'version' => '9.9.1',
                    'status' => 'STATUS4',
                    'message' => 'MESSAGE',
                    'error' => ApiClient::RESOURCE_NOT_FOUND,
                    'changeset' => array(
                        'file1' => (object)array(
                            'file' => 'file1',
                            'date' => '2017-01-01',
                        ),
                        'file2' => (object)array(
                            'file' => 'file2',
                        ),
                        'file3' => (object)array(
                            'file' => 'file3',
                            'date' => 'asda',
                        )
                    ),
                ),
            ),
            'themes' => array(),
        );


        $c->analyze($dummy);
        $t = $c->transientState;
        $this->assertTrue(isset($t['result']));
        $r = $t['result'];

        $this->assertTrue(isset($r['core']));
        $this->assertEquals(1, count($r['core']));
        $this->assertTrue(isset($r['core']['core']));
        $this->assertTrue(isset($r['core']['core']->message));
        $this->assertTrue(stripos($r['core']['core']->message, 'no api key') !== false);


        $this->assertTrue(isset($r['plugins']));
        $this->assertEquals(3, count($r['plugins']));
        $this->assertTrue(isset($r['plugins']['common-plugin']));
        $this->assertTrue(isset($r['plugins']['common-plugin']->message));
        $this->assertTrue(stripos($r['plugins']['common-plugin']->message, 'invalid api key') !== false);

        $this->assertTrue(isset($r['plugins']['another-plugin']));
        $this->assertTrue(isset($r['plugins']['another-plugin']->message));
        $this->assertTrue(stripos($r['plugins']['another-plugin']->message, 'API rate limit') !== false);

        $this->assertTrue(isset($r['plugins']['another-plugin2']));
        $this->assertTrue(isset($r['plugins']['another-plugin2']->message));
        $this->assertTrue(stripos($r['plugins']['another-plugin2']->message, 'not found') !== false);
        $this->assertEquals(3, count($r['plugins']['another-plugin2']->issues));


        $this->assertTrue(isset($r['themes']));

    }

    public function testCheckCore()
    {
        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . '/wp-admin/includes', 0777, true);
        @mkdir(ABSPATH . '/wp-includes', 0777, true);

        file_put_contents(ABSPATH . '/wp-admin/includes/update.php', '<?php');
        file_put_contents(ABSPATH . '/wp-admin/includes/file.php', '<?php');
        file_put_contents(ABSPATH . '/file1.php', '<?php');

        \WP_Mock::userFunction('get_bloginfo', array('return' => '9.9.9'));
        \WP_Mock::userFunction('get_transient', array('return' => false,));
        \WP_Mock::userFunction('set_transient', array('return' => false,));
        \WP_Mock::userFunction('get_core_checksums', array(
            'return' => array('9.9.9' => array(
                'file1.php' => md5('<?php'))
            ),
        ));

        $dummy = new \stdClass();
        $c = new Tests\Checksum($dummy, new \MockState(), $dummy, $dummy);
        $c->checkCore($dummy);
        $t = $c->transientState;
        $this->assertEquals(2, count($t['core'][0]['changeset']));
    }

    public function testCheckPlugin()
    {
        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . '/wp-admin/includes', 0777, true);
        @mkdir(WP_PLUGIN_DIR . '/foobar', 0777, true);
        file_put_contents(ABSPATH . 'wp-admin/includes/file.php', '<?php');
        file_put_contents(WP_PLUGIN_DIR. '/foobar/foobar.php', '<?php');

        \WP_Mock::userFunction('get_transient', array('return' => false,));
        \WP_Mock::userFunction('set_transient', array('return' => false,));

        $mockApiClient = new \MockApiClient(array(
            'plugin' => array(
                'foobar' => array(
                    '10.10' => (object)array('checksums' => array(
                        'foobar.php' => (object)array(
                            'hash' => 'asdsad',
                        )
                    )),
                )
            )
        ));


        $dummy = new \stdClass();
        $c = new Tests\Checksum($dummy, new \MockState(), $mockApiClient, $dummy);
        $c->checkPlugin((object)array(
            'parameters' => array(
                'id' => 'foobar/foobar.php',
                'plugin' => array('Name' => 'foobar', 'Version' => '10.10'),
            )
        ));

        $t = $c->transientState;
        $this->assertEquals(1, count($t['plugins'][0]['changeset']));
        $this->assertEquals('MODIFIED', $t['plugins'][0]['changeset']['foobar.php']->status);
    }

    public function testCheckPlugins()
    {
        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . '/wp-admin/includes', 0777, true);
        file_put_contents(ABSPATH . 'wp-admin/includes/plugin.php', '<?php');

        \WP_Mock::userFunction('get_plugins', array('return' => array(
            'foobar/foobar.php' => array(1,2,3),
            'ignoreme/ignoreme.php' => array(1,2,3),

        )));

        $dummy = new \stdClass();
        $settings = new \MockSettings();
        $backgroundProcess = new \MockBackgroundProcess();
        $c = new Tests\Checksum($settings, $dummy, $dummy, $dummy);
        $c->setBackgroundProcess($backgroundProcess);
        $c->checkPlugins(new \MockRequest());
        $this->assertEquals(1, count($backgroundProcess->jobs));
    }

    public function testCheckThemes()
    {
        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . 'wp-admin/includes', 0777, true);
        @mkdir(ABSPATH . 'wp-content/themes/mocktheme', 0777, true);
        file_put_contents(ABSPATH . 'wp-admin/includes/file.php', '<?php');
        file_put_contents(ABSPATH . 'wp-content/themes/mocktheme/foobar.php', '<?php');

        \WP_Mock::userFunction('wp_get_themes', array('return' => array(
            'mocktheme' => array('template' => 'mocktheme', 'Name' => 'Mock', 'Version' => '1.1'),
            'childtheme' => array('template' => 'mocktheme',),
        )));

        \WP_Mock::userFunction('get_theme_root', array(
            'return' => ABSPATH . 'wp-content/themes',
        ));

        $mockApiClient = new \MockApiClient(array(
            'theme' => array(
                'mocktheme' => array(
                    '1.1' => (object)array('checksums' => array(
                        'foobar.php' => (object)array(
                            'hash' => 'asdsad',
                        )
                    )),
                )
            )
        ));

        $dummy = new \stdClass();
        $c = new Tests\Checksum($dummy, $dummy, $mockApiClient, $dummy);
        $c->setBackgroundProcess(new \MockBackgroundProcess());
        $c->checkThemes(new \MockRequest());

        $t = $c->transientState;
        $this->assertEquals(1, count($t['themes'][0]['changeset']));
        $this->assertEquals('MODIFIED', $t['themes'][0]['changeset']['foobar.php']->status);
    }
}