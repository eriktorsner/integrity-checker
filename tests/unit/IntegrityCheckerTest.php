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

    public function testInit()
    {
        \WP_Mock::userFunction('is_admin', array('return_in_order' => array(false, true, true), 'times' => 3,));
        \WP_Mock::userFunction('load_plugin_textdomain', array('return' => null, 'times' => 2,));
        \WP_Mock::userFunction('dbDelta', array('return' => null, 'times' => 0,));

        \WP_Mock::userFunction('get_option', array(
            'args' => array('integrity-checker_dbversion', 0),
            'return' => 1,
            'times' => 2,
        ));

        \WP_Mock::userFunction('get_option', array(
            'args' => array('integrity-checker_version', '0.0.1'),
            'return' => '0.10.0',
            'times' => 2,
        ));

        $settings = new \MockSettings();
        $adminUiHooks = new \MockAdminUiHooks();
        $adminPage = new \MockAdminPage();
        $rest = new \MockRest();
        $backgroundProcess = new \MockBackgroundProcess();
        $ic = new integrityChecker($settings, $adminUiHooks, $adminPage, $rest, $backgroundProcess);

        $ic->init();

        $ic->init();
    }

    public function testInitUpdateDB()
    {
        global $wpdb;

        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . '/wp-admin/includes', 0777, true);
        file_put_contents(ABSPATH . 'wp-admin/includes/upgrade.php', "<?php\n");

        \WP_Mock::userFunction('is_admin', array('return_in_order' => array(false, true, true), 'times' => 1,));
        \WP_Mock::userFunction('load_plugin_textdomain', array('return' => null, 'times' => 1,));
        \WP_Mock::userFunction('dbDelta', array('return' => null, 'times' => 1,));
        \WP_Mock::userFunction('update_option', array(
            'args' => array('integrity-checker_dbversion', 1),
            'return' => null,
            'times' => 1,
        ));

        \WP_Mock::userFunction('get_option', array(
            'args' => array('integrity-checker_dbversion', 0),
            'return' => 0.2,
            'times' => 1,
        ));

        \WP_Mock::userFunction('get_option', array(
            'args' => array('integrity-checker_version', '0.0.1'),
            'return' => '0.10.0',
            'times' => 1,
        ));


        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_charset_collate')
            ->once()
            ->andReturn('utf-8');


        $settings = new \MockSettings();
        $adminUiHooks = new \MockAdminUiHooks();
        $adminPage = new \MockAdminPage();
        $rest = new \MockRest();
        $backgroundProcess = new \MockBackgroundProcess();
        $ic = new integrityChecker($settings, $adminUiHooks, $adminPage, $rest, $backgroundProcess);

        $ic->init();
    }

    public function testGetTestState()
    {
        $settings = new \MockSettings();
        $adminUiHooks = new \MockAdminUiHooks();
        $adminPage = new \MockAdminPage();
        $rest = new \MockRest();
        $backgroundProcess = new \MockBackgroundProcess();
        $ic = new integrityChecker($settings, $adminUiHooks, $adminPage, $rest, $backgroundProcess);

        $ret = $ic->getTestState((object)array(
            'session' => 'abc',
            'finished' => 0,
        ));

        $this->assertEquals(12, $ret->jobCount);

    }

    public function testRunScheduledScans()
    {
        $settings = new \MockSettings();
        $adminUiHooks = new \MockAdminUiHooks();
        $adminPage = new \MockAdminPage();
        $rest = new \MockRest();
        $backgroundProcess = new \MockBackgroundProcess();
        $ic = new integrityChecker($settings, $adminUiHooks, $adminPage, $rest, $backgroundProcess);

        \WP_Mock::userFunction('get_rest_url', array(
            'return' => 'http://foobar.com/wp-json'
        ));
        \WP_Mock::userFunction('wp_create_nonce', array(
            'return' => 'abc123xyz'
        ));
        \WP_Mock::userFunction('wp_remote_post');
        \WP_Mock::userFunction('wp_clear_scheduled_hook');

        $ic->runScheduledScans();

        $settings->enableScheduleScans = false;
        $ic->runScheduledScans();

    }
}
