<?php

namespace integrityChecker;

class ScanAllTest extends \PHPUnit_Framework_TestCase
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
        global $wpdb;
        $tableName = 'wp_integrity_checker_files';

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';

        $dummy = new \stdClass();
        $s = new Tests\ScanAll(new \MockSettings(), new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->start(new \MockRequest());
    }

    public function testScan()
    {
        global $wpdb;
        $tableName = 'wp_integrity_checker_files';

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';

        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . 'wp-admin/includes', 0777, true);
        @mkdir(ABSPATH . 'wp-includes', 0777, true);
        @mkdir(ABSPATH . 'wp-content/plugins/foobar', 0777, true);

        file_put_contents(ABSPATH . 'wp-admin/includes/file1.php', '<?php');
        file_put_contents(ABSPATH . 'wp-includes/file2.php', '<?php');
        file_put_contents(ABSPATH . 'wp-content/plugins/foobar/file3.php', '<?php');

        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('prepare');

        $dummy = new \stdClass();
        $bg = new \MockBackgroundProcess();
        $s = new Tests\ScanAll(new \MockSettings(), new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess($bg);
        $s->scan($dummy);

        $this->assertEquals(7, count($bg->jobs));
    }

    public function testScanFolder()
    {
        exec('rm -rf ' . ABSPATH . '*');
        @mkdir(ABSPATH . 'wp-admin/includes', 0777, true);
        @mkdir(ABSPATH . 'wp-content/plugins/foobar', 0777, true);

        file_put_contents(ABSPATH . 'wp-admin/includes/file.php', '<?php');
        file_put_contents(ABSPATH . 'wp-content/plugins/foobar/file3.php', '<?php');

        $job = (object)array(
            'parameters' => array('folder' => ABSPATH . 'wp-content/plugins', 'recursive' => 1),
        );

        global $wpdb;
        $tableName = 'wp_integrity_checker_files';

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('insert')
             ->times(2);

        $wpdb->shouldReceive('prepare')
             ->times(2);

        $wpdb->shouldReceive('get_row')
             ->times(2);

        $dummy = new \stdClass();
        $bg = new \MockBackgroundProcess();
        $s = new Tests\ScanAll(new \MockSettings(), new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess($bg);
        $s->scanFolder($job);

    }

    public function testAnalyze()
    {
        global $wpdb;
        $settings = new \MockSettings();

        \WP_Mock::userFunction('get_option', array(
            'args' => array($settings->slug . '_files_checkpoint', false),
            'return_in_order' => array(false, (object)array('ts' => time())),
        ));

        \WP_Mock::userFunction('update_option', array(
            'return' => true,
        ));

        $dummy = new \stdClass();
        $bg = new \MockBackgroundProcess();
        $s = new Tests\ScanAll(new \MockSettings(), new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess($bg);
        $s->analyze($dummy);

        $s->analyze($dummy);
        $t = $s->transientState;
        $this->assertTrue(isset($t['result']['ts']));

    }
}