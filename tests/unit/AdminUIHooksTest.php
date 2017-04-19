<?php
namespace integrityChecker;

class AdminUIHooksTest extends \PHPUnit_Framework_TestCase
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
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
    }

    public function testRegister()
    {
        \WP_Mock::userFunction('plugin_basename');

        $settings = new \MockSettings();
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
        $adminUIHooks->register();

        global $pagenow;
        $pagenow = 'update.php';
        $adminUIHooks->register();
    }

    public function testLoadPlugins()
    {
        \WP_Mock::userFunction('get_site_transient', array(
            'return' => new \MockUpdatePlugins(array(
                'checked' => array(
                    'pluginid' => 'fuubar'
                )
            ))
        ));

        $settings = new \MockSettings();
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
        $adminUIHooks->loadPlugins();

    }

    public function testOfferUpdate()
    {
        \WP_Mock::userFunction('get_site_transient', array(
            'return' => new \MockUpdatePlugins(array(
                'checked' => array(
                    'foobar' => 'fuubar'
                )
            ))
        ));
        \WP_Mock::userFunction('wp_kses', array(
            'return' => function($s) {
                return $s;
            }
        ));
        \WP_Mock::userFunction('wp_nonce_url', array(
            'return' => 'asdasdsa',
        ));
        \WP_Mock::userFunction('self_admin_url');

        $settings = new \MockSettings();
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
        $adminUIHooks->offerUpdate(
            'foobar',
            array(
                'Name' => 'foobar',
                'slug' => 'foobar',
            ),
            ''
        );
    }

    public function testPluginActionLinks()
    {
        \WP_Mock::userFunction('admin_url');

        $settings = new \MockSettings();
        $state = new \MockState();
        $adminUIHooks = new AdminUIHooks($settings, $state);
        $ret = $adminUIHooks->pluginActionLinks(array('first'));

        $this->assertEquals(2, count($ret));


    }
}