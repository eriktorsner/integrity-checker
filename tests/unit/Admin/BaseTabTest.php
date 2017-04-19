<?php
namespace integrityChecker;

class BaseTabTest extends \PHPUnit_Framework_TestCase
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
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
    }

    public function testGetScripts()
    {
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
        $tab->getScripts();
    }

    public function testGetStyles()
    {
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
        $tab->getStyles();
    }

    public function testGetUrl()
    {
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
        $tab->getUrl();
    }

    public function testRender()
    {
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
        $tab->render();
    }

    public function testDisplay()
    {
        $settings = new \MockSettings();
        $tab = new Admin\BaseTab($settings);
        $tab->display();
    }

}