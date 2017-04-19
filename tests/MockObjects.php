<?php

class MockBackgroundProcess
{
    public $jobs = array();
    public $session = 'abc123';
    public $cronIntervalIdentifier = 'tt_foobar';

    public function init() {}
    public function process($yield = false) {}
    public function jobCount() { return 12; }
    public function lastQueuePriority() { return 10; }
    public function registerActions() { }

    public function addJob($job, $priority = 10) {
        $this->jobs[] = $job;
    }

    public function addJobs($jobs, $priority = 10) {
        foreach ($jobs as $job) {
            $this->jobs[] = $job;
        }
    }

    public function initBackgroundProcess($test, $request, $limit)
    {

    }
}

class MockRequest
{
    public function __construct($arr = false)
    {
        if ($arr) {
            $this->arr = $arr;
        }

    }

    public function get_body()
    {
        if (isset($this->arr['body'])) {
            return $this->arr['body'];
        }
    }

    public function get_header($str)
    {
        if (isset($this->arr['headers'])) {
            if (isset($this->arr['headers'][$str])) {
                return $this->arr['headers'][$str];
            }
        }
    }

    public function get_param($name)
    {
        if (isset($this->arr['parameters'])) {
            if (isset($this->arr['parameters'][$name])) {
                return $this->arr['parameters'][$name];
            }
        }
    }
}

class MockResponse
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function get_headers()
    {
        return new MockHeaders($this->arr['headers']);
    }
}

class MockHeaders
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function getAll()
    {
        return $this->arr;
    }
}



class MockSettings
{
    public $slug = 'integrity-checker';
    public $fileOwners = 'www-data';
    public $fileGroups = 'www-data';
    public $fileMasks = '0644,0640,0600';
    public $folderMasks = '0755,0750,0700';
    public $maxFileSize = 2097152; //2 MB
    public $followSymlinks = 1;
    public $enableScheduleScans = true;
    public $scheduleScanChecksums = true;
    public $scheduleScanPermissions = true;
    public $scheduleScanSettings = true;

    public $checksumIgnore = array(
        'plugins' => array('ignoreme/ignoreme.php'),
    );
    public function userLevel()
    {
        return 'anonymous';
    }

    public $checkpointInterval = '1 month';
}

class MockState
{
    public $arr = array();
    public $result = array();
    public function __construct($arr = false, $result = false)
    {
        if ($arr) {
            $this->arr = $arr;
        }
        if ($result) {
            $this->result = $result;
        }
    }

    public function updateTestState()
    {

    }

    public function getTestState($testName)
    {
        if (isset($this->arr[$testName])) {
            return $this->arr[$testName];
        }

        return (object)array('started' => time());
    }

    public function storeTestResult($name, $result)
    {
        $this->arr[$name] = $result;
    }

    public function getTestResult($testName)
    {
        if (isset($this->result[$testName])) {
            return $this->result[$testName];
        }
    }
}

class MockApiClient
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function getQuota()
    {
        return (object)array(
            'access' => 'anonymous',
        );
    }

    public function getChecksums($type, $slug, $version)
    {
        return $this->arr[$type][$slug][$version];
    }

    public function getFile($type, $slug, $version, $file)
    {
        return array(
            'response' => array(
                'code' => $this->arr['response']['code'],
                'message' => $this->arr['response']['message'],
            ),
            'body' => $this->arr['body'],
            'headers' => $this->arr['headers'],
        );
    }
}

class MockTestFactory
{
    public $arr = false;
    public function __construct($arr = false)
    {
        $this->arr = $arr;
    }

    public function getTestObject($str)
    {
        if ($this->arr === false) {
            return new MockTest($str);
        }
        if (isset($this->arr[$str])) {
            return new MockTest($str);
        }
    }

    public function hasTest($name)
    {
        return isset($this->arr[$name]);
    }

    public function getTestNames()
    {
        return array_keys($this->arr);
    }
}

class MockTest extends \integrityChecker\Tests\BaseTest
{
    public function __construct($name, $state = null)
    {
        $this->name = $name;
        if (!$state) {
            $state = new \MockState();
        }

        parent::__construct(new MockSettings(), $state, null, null);
    }

    public function setSession($session)
    {
        $this->session = $session;
    }

    public function setBackgroundProcess($p)
    {
        $this->backgroundProcess = $p;

    }

    public function getTestState($name)
    {
        if ($this->arr[$name]) {
            return $this->arr[$name];
        }
    }

    public function analyze($job)
    {

    }

    public function startWDependency($dep, $request, $limit)
    {
        $this->initBackgroundProcess($dep, $request, $limit);
    }

    public function start($request)
    {
        $this->session = 'startedbymockstart';
    }
}

class MockAdminUiHooks
{
    public function register() {}
}

class MockAdminPage
{
    public function register() {}
}

class MockRest
{
    public function register() {}
}

class MockProcess
{

}

class MockFileDiff
{

}

class MockTheme
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function get($name)
    {
        return $this->arr[$name];
    }

    public function __get($name)
    {
        return $this->arr[$name];
    }
}

class MockUpdatePlugins
{
    public function __construct($arr)
    {
        $this->arr = $arr;
        $this->checked = $arr['checked'];
    }

    public function __get($name)
    {
        return $this->arr[$name];
    }
}

class WP_REST_Response
{
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function header($name, $value)
    {

    }
}