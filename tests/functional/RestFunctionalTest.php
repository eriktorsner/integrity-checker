<?php

class RestFunctionalTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        setUpWp();
    }

    public function testRest1()
    {
        global $testUrl;

        $ret = file_get_contents($testUrl);
        $this->assertTrue(strpos($ret, 'Just another WordPress site') !== false);
    }
}