<?php

class RestFunctionalTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        setUpWp();
    }

    public function testTemp()
    {
        //require_once ABSPATH . 'index.php';

    }

    public function testRest1()
    {
        global $testUrl;

        $this->assertTrue(true);
        //$ret = file_get_contents($testUrl);
        //$this->assertTrue(strpos($ret, 'Just another WordPress site') !== false);

    }
}