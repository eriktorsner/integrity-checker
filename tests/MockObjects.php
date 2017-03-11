<?php

class MockBackgroundProcess
{
    public $jobs = array();
    public $session = 'abc123';

    public function init() {}
    public function process($yield = false) {}
    public function jobCount() { return 0; }
    public function lastQueuePriority() { return 10; }

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
}

class MockSettings
{
    public $slug = 'integrity-checker';
    public $fileOwners = 'www-data';
    public $fileGroups = 'www-data';
    public $fileMasks = '0644,0640,0600';
    public $folderMasks = '0755,0750,0700';
    public $maxFileSize = 2097152; //2 MB
}

class MockState
{
    public $arr = array();
    public function __construct($arr = false)
    {
        if ($arr) {
            $this->arr = $arr;
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
    }

    public function storeTestResult($name, $result)
    {
        $this->arr[$name] = $result;
    }
}

class MockApiClient
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function getChecksums($type, $slug, $version)
    {
        return $this->arr[$type][$slug][$version];
    }
}

class MockTestFactory
{
    public function getTestObject($str)
    {
        return new MockTest($str);
    }
}

class MockTest extends \integrityChecker\Tests\BaseTest
{
    public function __construct($name, $state = null)
    {
        $this->name = $name;
        $this->state = $state;
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

    public function startWDependency($dep, $request, $limit)
    {
        $this->initBackgroundProcess($dep, $request, $limit);
    }

    public function start($request)
    {
        $this->session = 'startedbymockstart';
    }
}