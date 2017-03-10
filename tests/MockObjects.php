<?php

class MockSettings
{

}

class MockBackgroundProcess
{
    public function init() {}
    public function addJob($job, $priority = 10) {}
    public function process($yield = false) {}
    public function jobCount() { return 0; }
    public function lastQueuePriority() { return 10; }


}

class MockRequest
{
    public function get_body()
    {

    }
}

class MockState
{
    public function updateTestState() {

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