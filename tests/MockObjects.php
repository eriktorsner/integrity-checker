<?php

class MockSettings
{

}

class MockBackgroundProcess
{
    public function init() {}
    public function addJob($job, $priority = 10) {}
    public function process($yield = false) {}
    public function jobCount() {
        return 0;
    }

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