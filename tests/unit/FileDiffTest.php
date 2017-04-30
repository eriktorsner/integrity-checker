<?php
namespace integrityChecker;

/**
 * @__runTestsInSeparateProcesses
 * @__preserveGlobalState disabled
 */
class FileDiffTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        // Download wp ($downloadOnly = true)
        //setUpWp(true);

        require_once __DIR__ . '/../class-wp-error.php';
        require_once __DIR__ . '/../MockWpRestResponse.php';
    }
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testGetCore()
    {
        \WP_Mock::userFunction('wp_text_diff', array(
            'return' => "content\ndoesnt\nmatter\n",
        ));
        $client = new \MockApiClient(array(
            'response' => array('code' => 200, 'message' => ''),
            'body' => 'some content',
            'headers' => new \MockHeaders(array(
                'x-checksum-diff-remain' => '10',
            )),
        ));

        $f = new FileDiff($client, new \MockWpApi(array(
            'version' => '4.7.4',
            'abspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/'
        )));

        $ret = $f->getDiff('core', 'core', 'readme.html');
        $this->assertEquals("content\ndoesnt\nmatter\n", $ret->data);

    }

    public function testGetPlugin()
    {
        \WP_Mock::userFunction('wp_text_diff', array(
            'return' => "content\ndoesnt\nmatter\n",
        ));
        /*\WP_Mock::userFunction('wp_cache_get', array(
            'return' => array('' => array(
                'akismet/akismet.php' => array(
                    'Version' => '1.1.1',
                )
            )),
        ));*/
        \WP_Mock::userFunction('get_plugins', array(
            'return' => array(
                'akismet/akismet.php' => array(
                    'Version' => '1.1.1',
                )
            ),
        ));

        $client = new \MockApiClient(array(
            'response' => array('code' => 200, 'message' => ''),
            'body' => 'some content',
            'headers' => new \MockHeaders(array(
                'x-checksum-diff-remain' => '10',
            )),
        ));

        $wpApi = new \MockWpApi(array(
            'abspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/',
            'pluginspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/wp-content/plugins',
        ));

        $f = new FileDiff($client, $wpApi);

        $ret = $f->getDiff('plugin', 'akismet', 'readme.txt');
        $this->assertEquals("content\ndoesnt\nmatter\n", $ret->data);
    }

    public function testGetTheme()
    {
        \WP_Mock::userFunction('wp_text_diff', array(
            'return' => "content\ndoesnt\nmatter\n",
        ));
        \WP_Mock::userFunction('wp_get_themes', array(
           'return' => array(
               'twentyfifteen' => new \MockTheme(array(
                   'Version' => '1.2',
                   'theme_root' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/wp-content/themes',
                   'stylesheet' => 'twentyfifteen',
               )),
           )
        ));
        $client = new \MockApiClient(array(
            'response' => array('code' => 200, 'message' => ''),
            'body' => 'some content',
            'headers' => new \MockHeaders(array(
                'x-checksum-diff-remain' => '10',
            )),
        ));

        $wpApi = new \MockWpApi(array(
            'abspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/',
            'pluginspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/wp-content/plugins',
        ));

        $f = new FileDiff($client, $wpApi);

        $f = new FileDiff($client, new \MockWpApi(array(
            'abspath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/',
            'themespath' => INTEGRITY_CHECKER_ROOT . '/tests/fixtures/difftests/wp-content/themes',
        )));

        $ret = $f->getDiff('theme', 'twentyfifteen', 'readme.txt');
        $this->assertEquals("content\ndoesnt\nmatter\n", $ret->data);
    }
}