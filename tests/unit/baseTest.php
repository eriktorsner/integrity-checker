<?php

class baseTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        \WP_Mock::setUp();
    }

    public function tearDown() {
        \WP_Mock::tearDown();
    }

    public function testBoot()
    {
    }
}