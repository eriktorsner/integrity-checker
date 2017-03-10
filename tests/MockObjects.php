<?php

class MockBackgroundProcess
{
    public $session = 'abc123';

    public function init() {}
    public function addJob($job, $priority = 10) {}
    public function process($yield = false) {}
    public function jobCount() { return 0; }
    public function lastQueuePriority() { return 10; }

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
}

class MockState
{
    public function __construct($arr = false)
    {
        if ($arr) {
            $this->arr = $arr;
        }
    }

    public function updateTestState() {

    }

    public function getTestState($testName)
    {
        if (isset($this->arr[$testName])) {
            return $this->arr[$testName];
        }
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

class MockTest
{
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setBackgroundProcess($p)
    {

    }
}