<?php
namespace integrityChecker;

class DiagnosticTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testGet()
    {
        global $wpdb, $_SERVER;

        $options = array(
            array('permalink_structure', 'structure', 1),
            array('category_base', 'catbase', 1),
            array('tag_base', 'tagbase', 1),
        );

        $functions = array(
            array('name' => 'get_locale', 'args' => null, 'return' => 'fo/BA', 'times' => 1),
            array('name' => 'wp_get_theme', 'args' => null, 'times' => 1,  'return' => array(
                (object)array('name' => 'footheme',),
            ),),
            array('name' => 'wp_get_themes', 'args' => null, 'times' => 1, 'return' => array(
                array(
                    (object)array('name' => 'parenttheme', 'version' => '1.1.1', 'parent' => ''),
                    (object)array('name' => 'footheme', 'version' => '0.1', 'parent' => 'parenttheme'),
                ),
            ),),
            array('name' => 'get_plugins', 'args' => null, 'times' => 1, 'return' => array(
                array(
                    'plug1' => array('Name' => 'plug1', 'Version' => '1.1.1', 'Author' => 'fooauthor'),
                    'plug2' => array('Name' => 'plug2', 'Version' => '2.2', 'Author' => 'fooauthor2'),
                ),
            ),),
            array('name' => 'is_plugin_active', 'args' => '*', 'return' => array(true, false), 'times' => 2),
        );
        foreach ($options as $option) {
            \WP_Mock::userFunction('get_option', array(
                'args' => $option[0],
                'return_in_order' => $option[1],
                'times' => $option[2],
            ));
        }

        foreach ($functions as $function) {
            \WP_Mock::userFunction($function['name'], array(
                'args' => $function['args'],
                'return_in_order' => $function['return'],
                'times' => $function['times'],
            ));
        }

        $wpdb = \Mockery::mock( '\WPDB' );
        $wpdb->prefix = 'wp_';
        $wpdb->dbname = 'fooname';
        $wpdb->dbhost = 'foohost';
        $wpdb->charset = 'some-charset';
        $wpdb->shouldReceive('db_version')
             ->once()
             ->andReturn(10);

        $_SERVER['SERVER_SOFTWARE'] = 'phpunit';

        $d = new Diagnostic();
        $ret = $d->get();
    }
}
