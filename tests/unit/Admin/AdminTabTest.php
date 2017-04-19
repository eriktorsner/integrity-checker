<?php
namespace integrityChecker;

class AdminTabTest extends \PHPUnit_Framework_TestCase
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
        $tab = new Admin\AboutTab(new \MockSettings());
    }

    public function testGetScripts()
    {
        $tab = new Admin\AboutTab(new \MockSettings());
        $tab->getScripts();
    }

    public function testRender()
    {
        $tab = new Admin\AboutTab(new \MockSettings());
        //$tab->render();
    }

}