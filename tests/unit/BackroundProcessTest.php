<?php
namespace integrityChecker;

class BackgroundProcessTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstruct()
    {
        $testFactory = new \MockTestFactory();
        $bg          = new BackgroundProcess($testFactory);
    }

    public function testInit()
    {
        $testFactory = new \MockTestFactory();
        $bg          = new BackgroundProcess($testFactory);
        $bg->init();
        $this->assertTrue(strlen($bg->session) > 10);

        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $this->assertEquals('abc123', $bg->session);
    }

    public function testRegisterRestEndPoints()
    {
        global $mockRestEndpoints;
        $mockRestEndpoints = array();

        \WP_Mock::userFunction('register_rest_route', array(
            'return'=> function($base, $endPoint, $args) {
                global $mockRestEndpoints;
                $mockRestEndpoints[] = array($base, $endPoint, $args);
            }
        ));

        $testFactory = new \MockTestFactory();
        $bg          = new BackgroundProcess($testFactory);
        $bg->registerRestEndPoints();

        $this->assertTrue($mockRestEndpoints[0][0] == 'integrity-checker/v1');
        $this->assertTrue($mockRestEndpoints[0][1] == 'background/(?P<session>[a-zA-Z0-9-]+)');
        $this->assertEquals('GET', $mockRestEndpoints[0][2]['methods'][0]);

        $mockRestEndpoints = array();
    }

    public function testSetters()
    {
        $testFactory = new \MockTestFactory();
        $bg          = new BackgroundProcess($testFactory);

        $bg->setMemoryLimit(100);
        $bg->setTimeLimit(25);
        $bg->setPrefix('foo_prefix');
    }

    public function testRegisterCron()
    {
        $testFactory = new \MockTestFactory();
        $bg          = new BackgroundProcess($testFactory);

        \WP_Mock::expectActionAdded('tt_bgprocess_cron', array($bg, 'handleCronHealthCheck'));
        \WP_Mock::expectFilterAdded('cron_schedules', array($bg, 'scheduleCronHealthCheck'));

        $bg->registerActions();

    }

    public function testProcessAlreadyRunning()
    {
        // already running
        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_lock_abc123',
            'return_in_order' => array(time()),
            'times' => 1,
        ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->process();
    }

    public function testProcess1()
    {
        $queue = array(
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 10),
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 20),
        );

        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_lock_abc123',
            'return_in_order' => array(100),
            'times' => 1,
        ));
        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_queue_abc123',
            'return_in_order' => array(
                $queue,
                array($queue[1]),
                array(),
            ),
            'times' => 3,
        ));
        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_teststate_abc123',
            'return' => array('some' => 'state'),
            'times' => 2,
        ));
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_bgprocess_lock_abc123', '*'),
            'times' => 1,
        ));
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_bgprocess_queue_abc123', '*'),
            'times' => 2,
        ));
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_teststate_abc123', array('some' => 'state')),
            'times' => 1,
        ));
        \WP_Mock::userFunction('delete_transient', array(
            'args' => array('tt_bgprocess_lock_abc123'),
            'times' => 1,
        ));
        \WP_Mock::userFunction('delete_transient', array(
            'args' => array('tt_bgprocess_queue_abc123'),
            'times' => 1,
        ));
        \WP_Mock::userFunction('wp_next_scheduled', array(
            'args' => array('tt_bgprocess_cron'),
            'return' => false,
            'times' => 1,
        ));
        \WP_Mock::userFunction('wp_schedule_event', array(
            'args' => array('*', 'tt_bgprocess_cron_interval', 'tt_bgprocess_cron'),
            'return' => true,
            'times' => 1,
        ));


        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->process();

    }

    public function testProcess2()
    {
        $queue = array(
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 10),
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 20),
        );

        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_lock_abc123',
            'return_in_order' => array(100),
            'times' => 1,
        ));
        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_queue_abc123',
            'return_in_order' => array(
                $queue,
                array($queue[1]),
                array(),
            ),
            'times' => 1,
        ));
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_bgprocess_lock_abc123', '*'),
            'times' => 1,
        ));
        \WP_Mock::userFunction('delete_transient', array(
            'args' => array('tt_bgprocess_lock_abc123'),
            'times' => 1,
        ));

        \WP_Mock::userFunction('wp_next_scheduled', array(
            'args' => array('tt_bgprocess_cron'),
            'return' => time(),
            'times' => 1,
        ));
        /*\WP_Mock::userFunction('get_rest_url', array(
            'return' => 'http://example.com/wp-json',
            'times' => 1,
        ));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return' => true,
            'times' => 1,
        ));*/

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->setTimeLimit(-1);
        $bg->process();
    }

    public function testAddJob()
    {
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_bgprocess_queue_abc123', '*'),
            'times' => 1,
        ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->addJob((object)array('class' => 'foo'));
    }

    public function testAddJob2()
    {
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_bgprocess_queue_abc123', '*'),
            'times' => 1,
        ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->addJobs(array(
            (object)array('class' => 'foo'),
            (object)array('class' => 'foo'),
        ));
    }

    public function testScheduleCronHealthCheck()
    {
        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);

        \WP_Mock::userFunction('get_rest_url', array(
            'return' => 'http://example.com/wp-json',
        ));

        \WP_Mock::userFunction('wp_remote_get', array(
            'return' => true,
        ));

        $ret = $bg->scheduleCronHealthCheck(array());
        $this->assertEquals(1, count($ret));
        $this->assertEquals($ret['tt_bgprocess_cron_interval']['interval'], 300);
        $this->assertEquals($ret['tt_bgprocess_cron_interval']['display'], 'Every 5 Minutes');
    }

    public function testHandleCronHealthCheck()
    {
        global $wpdb;

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';
        $wpdb->options = 'options';

        $wpdb->shouldReceive('get_results')
             ->once()
            ->andReturn(array(
                (object)array('name' => 'tt_bgprocess_lock_abc999', 'value' => time()),
                (object)array('name' => 'tt_bgprocess_lock_abc123', 'value' => 200),
            ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->handleCronHealthCheck();

        $wpdb->shouldReceive('get_results')
             ->once()
             ->andReturn(array());

        $ts = time();
        \WP_Mock::userFunction('wp_next_scheduled', array(
            'args' => array('tt_bgprocess_cron'),
            'return' => $ts,
            'times' => 1,
        ));
        \WP_Mock::userFunction('wp_unschedule_event', array(
            'args' => array($ts, 'tt_bgprocess_cron'),
            'return' => true,
            'times' => 1,
        ));


        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->handleCronHealthCheck();

    }

    public function testLastQueuePriority()
    {
        $queue = array(
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 10),
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 20),
        );

        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_queue_abc123',
            'return_in_order' => array($queue, array()),
            'times' => 2,
        ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $prio = $bg->lastQueuePriority();
        $this->assertEquals(20, $prio);

        $prio = $bg->lastQueuePriority();
        $this->assertEquals(0, $prio);
    }

    public function testJobCount()
    {
        $queue = array(
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 10),
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 20),
        );

        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_queue_abc123',
            'return_in_order' => array($queue),
            'times' => 1,
        ));

        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $jobCount = $bg->jobCount();
        $this->assertEquals(2, $jobCount);

    }

    public function testOnShutdown()
    {
        $testFactory = new \MockTestFactory();
        $bg = new BackgroundProcess($testFactory);
        $bg->onShutdown();

        $queue = array(
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 10),
            (object)array('class' => 'foo', 'method' => 'analyze', 'priority' => 20),
        );

        \WP_Mock::userFunction('get_transient', array(
            'args' => 'tt_bgprocess_queue_abc123',
            'return_in_order' => array(
                $queue,
            ),
            'times' => 1,
        ));

        $bg = new BackgroundProcess($testFactory);
        $bg->init('abc123');
        $bg->onShutdown();


    }
}