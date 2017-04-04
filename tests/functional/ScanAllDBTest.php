<?php
namespace integrityChecker;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ScanAllDBTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        echo "*********  Setting up " . __CLASS__ . "\n\n";
        setUpWp();
    }

    public function setUp()
    {
        require_once ABSPATH . 'wp-load.php';
    }

    public function testDbCreation()
    {
        global $wpdb;
        //$this->createDb('integrity-checker');

        $ret = $wpdb->get_results("SHOW TABLES LIKE 'wp_integrity_checker_files'");
        $this->assertEquals(1, count($ret));
    }

    public function testFindFiles()
    {
        global $wpdb;

        @exec('rm -rf ' . ABSPATH . 'testfile*.php');
        $wpdb->get_row("delete from wp_integrity_checker_files");

        $job = (object)array(
            'parameters' => array(
                'folder' => ABSPATH,
                'recursive' => false,
            ),
        );

        $settings = new \MockSettings();
        $scanAll = new Tests\ScanAll(
            $settings,
            (object)array(),
            (object)array(),
            (object)array()
        );

        // Add a random file before continuing
        file_put_contents(ABSPATH . 'testfile.php', 'some random content');

        /*
         * Scan for new, delted and modified files
         */

        $scanAll->transientState = array('scanStart' => 10);
        callPrivate($scanAll, 'markLatestFilesDeleted', 10);
        $scanAll->scanFolder($job);

        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='wp-login.php'");
        $this->assertEquals(1, $ret->version);
        $this->assertEquals(null, $ret->deleted);
        $this->assertEquals(0, $ret->isdir);
        $this->assertEquals(0, $ret->islink);

        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='wp-admin'");
        $this->assertEquals(1, $ret->version);
        $this->assertEquals(null, $ret->deleted);
        $this->assertEquals(1, $ret->isdir);
        $this->assertEquals(0, $ret->islink);

        // count total nr of files and folders right now
        $rowCount = $wpdb->get_var("select count(*) from wp_integrity_checker_files;");

        // scan folder again with no file changes
        $scanAll->transientState = array('scanStart' => 20);
        callPrivate($scanAll, 'markLatestFilesDeleted', 20);
        $scanAll->scanFolder($job);

        // count total nr of files after 2nd scan and check that it's the same
        $rowCount2 = $wpdb->get_var("select count(*) from wp_integrity_checker_files;");
        $this->assertSame($rowCount, $rowCount2);

        // Add one, delete one and modify one
        file_put_contents(ABSPATH . 'testfile2.php', 'some random content');
        exec('rm -rf ' . ABSPATH . 'testfile.php');
        file_put_contents(
            ABSPATH . 'wp-config.php',
            file_get_contents(ABSPATH . 'wp-config.php') . "\n\n// An evil comment"
        );

        $scanAll->transientState = array('scanStart' => 30);
        callPrivate($scanAll, 'markLatestFilesDeleted', 30);
        $scanAll->scanFolder($job);

        // testfile2.php was added, it should have version 1 and be found at 30
        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='testfile2.php'");
        $this->assertEquals(1, $ret->version);
        $this->assertEquals(30, $ret->found);

        // wp-config.php was modified, it should have a version 1 still found at 10
        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='wp-config.php' AND version=1");
        $this->assertEquals(1, $ret->version);
        $this->assertEquals(10, $ret->found);
        // ...it should also have a version 2, found at 30
        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='wp-config.php' AND version=2");
        $this->assertEquals(2, $ret->version);
        $this->assertEquals(30, $ret->found);

        // testfile.php was deleted, there should still be a version 1 of it, found at 10, deleted at 30
        $ret = $wpdb->get_row("select * from wp_integrity_checker_files WHERE name='testfile.php'");
        $this->assertEquals(1, $ret->version);
        $this->assertEquals(10, $ret->found);
        $this->assertEquals(30, $ret->deleted);

        /*
         * Truncate history
         */
        // Truncating all history before 20
        $secondsToRemove = time() - 20;
        $scanAll->truncateHistory((object)array('deleteScanHistoryRange' => '-' . $secondsToRemove . ' seconds'));
    }



    private function createDb($slug)
    {
        global $wpdb;

        $ic = new integrityChecker(
            (object)array('integrity-checker' => $slug),
            (object)array(),
            (object)array(),
            (object)array(),
            (object)array()
        );

        $ic->createTables();
    }
}
