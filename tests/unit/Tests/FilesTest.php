<?php

namespace integrityChecker;

class FilesTest extends \PHPUnit_Framework_TestCase
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
        $tf = new \MockTestFactory();
        $state = new \MockState(array(
            'scanall' => (object)array(
                'state' => 'finished',
                'finished' => time() - 10,
            )
        ));

        $req = new \MockRequest(array('body' => json_encode(array("source" => "manual"))));

        $dummy = new \stdClass();
        $f = new Tests\Files($dummy, $state, $dummy, $tf);
        $f->setBackgroundProcess(new \MockBackgroundProcess());
        $f->start($req);
    }

    public function testAnalyze()
    {
        $t = $this->_testAnalyze(new \MockSettings());
        $this->assertEquals(6, $t['result']['total']);
        $this->assertEquals(2, $t['result']['acceptable']);
        $this->assertEquals(4, $t['result']['unacceptable']);

        $t = $this->_testAnalyze((object)array('fileOwners' => null, 'fileGroups' => false));
    }

    private function _testAnalyze($settings = false)
    {
        global $wpdb;
        $tableName = 'wp_integrity_checker_files';

        $mockSettings = new \MockSettings();
        if ($settings) {
            foreach ($settings as $key => $value) {
                $mockSettings->$key = $value;
            }
        }

        $tf = new \MockTestFactory();
        $state = new \MockState(array(
            'scanall' => (object)array(
                'state' => 'finished',
                'finished' => time() - 10,
            )
        ));

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_results')
             ->once()
             ->with( "select * from $tableName WHERE checkpoint=0 LIMIT 0, 2000")
             ->andReturn(array(
                 (object)array('id' => 1, 'mask' => 644, 'isdir' => 0, 'fileowner' => 'www-data', 'filegroup' => 'www-data'),
                 (object)array('id' => 2, 'mask' => 755, 'isdir' => 1, 'fileowner' => 'www-data', 'filegroup' => 'www-data'),
                 (object)array('id' => 3, 'mask' => 777, 'isdir' => 0, 'fileowner' => 'www-data', 'filegroup' => 'www-data'),
                 (object)array('id' => 4, 'mask' => 777, 'isdir' => 1, 'fileowner' => 'www-data', 'filegroup' => 'www-data'),
                 (object)array('id' => 5, 'mask' => 644, 'isdir' => 0, 'fileowner' => 'nobody', 'filegroup' => 'www-data'),
                 (object)array('id' => 6, 'mask' => 755, 'isdir' => 1, 'fileowner' => 'www-data', 'filegroup' => 'nobody'),
             ));

        $wpdb->shouldReceive('get_results')
             ->once()
             ->with( "select * from $tableName WHERE checkpoint=0 LIMIT 2000, 2000")
             ->andReturn(array());

        $wpdb->shouldReceive('get_col')
             ->andReturn(array('www-data'));

        $wpdb->shouldReceive('query')
            ->times(3);

        $dummy = new \stdClass();
        $f = new Tests\Files($mockSettings, new \MockState(), $dummy, $dummy);
        $f->setBackgroundProcess(new \MockBackgroundProcess());
        $f->analyze($dummy);

        return $f->transientState;

    }
}