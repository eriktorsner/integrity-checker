<?php
namespace integrityChecker;

use Stripe\Error\Api;

class ApiClientTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
        require_once INTEGRITY_CHECKER_ROOT . '/tests/class-wp-error.php';
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstruct()
    {
        define('INTEGRITY_CHECKER_URL', 'https://api.wpessentials.io/v1');
        $c = new ApiClient();
    }

    public function testGetLastError()
    {
        $c = new ApiClient();
        $this->assertEquals(0, $c->getLastError());
    }

    public function testGetQuota()
    {
        $c = new ApiClient();

        \WP_Mock::userFunction('update_option', array());
        \WP_Mock::userFunction('is_wp_error', array(
            'return' => function($obj) {
                return $obj instanceof \WP_Error;
            }
        ));
        \WP_Mock::userFunction('get_option', array(
            'return_in_order' => array(false, 'abc123'),
        ));
        \WP_Mock::userFunction('wp_remote_post', array(
            'return_in_order' => array(array('response' => array('code' => 400),),)
        ));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => json_encode(array(
                        'hourlyLimit' => -1,
                        'dailyLimit' => -1,
                        'monthlyLimit' => -1,
                        'maxSites' => 10,
                    )),
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 401),
                    'body' => null,
                ),
                new \WP_Error('foo', 'bar'),
            )
        ));
        \WP_Mock::userFunction('set_transient');

        // Test with no api key
        $ret = $c->getQuota();
        $this->assertEquals(ApiClient::NO_APIKEY, $c->getLastError());

        // Test with normal response
        $ret = $c->getQuota();
        $this->assertEquals('Unlimited', $ret->rateLimit);
        $this->assertEquals('', $ret->resetIn);
        $this->assertEquals('', $ret->currentUsage);

        // Test with 401 response
        $ret = $c->getQuota();
        $this->assertEquals(null, $ret);
        $this->assertEquals(2, $c->getLastError());

        // Test when a wp_remote_get returns a WP_Error
        $ret = $c->getQuota();
        $this->assertEquals(null, $ret);
    }

    public function testParseQuota()
    {
        // Getting API key via a
        $c = new ApiClient();

        $response = (object)array(
            'hourlyLimit' => -1,
            'dailyLimit' => -1,
            'monthlyLimit' => -1,
            'maxSites' => 10,
        );

        callPrivate($c, 'parseQuota', $response);
        $this->assertEquals('Unlimited', $response->rateLimit);
        $this->assertEquals('', $response->resetIn);
        $this->assertEquals('', $response->currentUsage);

        $response = (object)array(
            'hourlyLimit' => 50,
            'hourlyResetIn' => 1000,
            'hourlyCurrent' => 25,
            'dailyLimit' => 50,
            'dailyResetIn' => 3600 * 24 * 2,
            'dailyCurrent' => 30,
            'monthlyLimit' => 50,
            'monthlyResetIn' => 3600 * 24 * 10,
            'monthlyCurrent' => 40,
            'maxSites' => -1,
        );

        callPrivate($c, 'parseQuota', $response);
        $this->assertEquals('50/hour + 50/day + 50/month', $response->rateLimit);
        $this->assertEquals('1000 s / 48 h / 10 days', $response->resetIn);
        $this->assertEquals('25 / 30 / 40', $response->currentUsage);
        $this->assertEquals('Unlimited', $response->siteLimit);

    }

    public function testVerifyApiKey()
    {
        \WP_Mock::userFunction('update_option', array());
        \WP_Mock::userFunction('update_option', array(
            'args' => array('wp_checksum_siteid', 'foobar-123'),
        ));
        \WP_Mock::userFunction('is_wp_error', array(
            'return' => function($obj) {
                return $obj instanceof \WP_Error;
            }
        ));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => json_encode(array(
                        'hourlyLimit' => -1,
                        'dailyLimit' => -1,
                        'monthlyLimit' => -1,
                        'maxSites' => 10,
                    )),
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 401),
                    'body' => null,
                ),
            )
        ));

        $c = new ApiClient();
        $this->assertTrue($c->verifyApiKey('abc123'));
        $ret = $c->verifyApiKey('abc123');
        $this->assertTrue($ret instanceof \WP_Error);
    }

    public function testRegisterEmail()
    {
        \WP_Mock::userFunction('update_option', array());
        \WP_Mock::userFunction('get_option', array(
            'return_in_order' => array(false, 'abc123'),
        ));
        \WP_Mock::userFunction('wp_remote_post', array(
            'return_in_order' => array(
                array('response' => array('code' => 400),),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => json_encode(array('apikey' => 'abc123')),
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 401),
                    'body' => null,
                ),
            )
        ));
        \WP_Mock::userFunction('is_wp_error', array(
            'return' => function($obj) {
                return $obj instanceof \WP_Error;
            }
        ));

        $c = new ApiClient();
        // No api key
        $ret = $c->registerEmail('foobar');
        $this->assertEquals(ApiClient::NO_APIKEY, $c->getLastError());

        $ret = $c->registerEmail('foobar');
        $this->assertEquals(0, $c->getLastError());

        $ret = $c->registerEmail('foobar');
        $this->assertEquals(ApiClient::INVALID_APIKEY, $c->getLastError());
    }

    public function testGetChecksums()
    {
        \WP_Mock::userFunction('update_option', array());
        \WP_Mock::userFunction('get_option', array(
            'return_in_order' => array('abc123'),
        ));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => json_encode(array()),
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 401),
                    'body' => null,
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 404),
                    'body' => null,
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 429),
                    'body' => null,
                ),
            )
        ));

        $c = new ApiClient();
        $ret =$c->getChecksums('plugin', 'slug', '1.1');
        $this->assertEquals(0, $c->getLastError());

        $ret =$c->getChecksums('plugin', 'slug', '1.1');
        $this->assertEquals(ApiClient::INVALID_APIKEY, $c->getLastError());

        $ret =$c->getChecksums('plugin', 'slug', '1.1');
        $this->assertEquals(ApiClient::RESOURCE_NOT_FOUND, $c->getLastError());

        $ret =$c->getChecksums('plugin', 'slug', '1.1');
        $this->assertEquals(ApiClient::RATE_LIMIT_EXCEEDED, $c->getLastError());
    }

    public function testGetFile()
    {
        \WP_Mock::userFunction('update_option', array());
        \WP_Mock::userFunction('get_option', array(
            'return_in_order' => array(false, 'abc123'),
        ));
        \WP_Mock::userFunction('wp_remote_post', array(
            'return_in_order' => array(array('response' => array('code' => 400),),)
        ));
        \WP_Mock::userFunction('wp_remote_get', array(
            'return_in_order' => array(
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => 'contents of readme.txt',
                ),
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('foobar' => 'novalue')
                    )),
                    'response' => array('code' => 401),
                    'body' => null,
                ),
            )
        ));

        $c = new ApiClient();
        $ret =$c->getFile('plugin', 'slug', '1.1', 'readme.txt');
        $this->assertEquals(null, $ret);

        $ret =$c->getFile('plugin', 'slug', '1.1', 'readme.txt');
        $this->assertEquals('contents of readme.txt', $ret['body']);

    }

    public function testGetApiKey()
    {
        // Getting API key via a
        $c = new ApiClient();

        \WP_Mock::userFunction('get_option', array(
            'return_in_order' => array('abc123', false),
        ));
        \WP_Mock::userFunction('update_option', array(
            'args' => array('integrity-checker_siteid', 'foobar-123'),
        ));
        \WP_Mock::userFunction('update_option', array(
            'args' => array('integrity-checker_apikey', 'abc123'),
        ));

        \WP_Mock::userFunction('is_wp_error', array(
            'return' => function($obj) {
                return $obj instanceof \WP_Error;
            }
        ));
        \WP_Mock::userFunction('wp_remote_post', array(
            'return_in_order' => array(
                array(
                    'http_response' => new \MockResponse(array(
                        'headers' => array('x-checksum-site-id' => 'foobar-123')
                    )),
                    'response' => array('code' => 200),
                    'body' => json_encode(array('apikey' => 'abc123')),
                ),
                null,
            )
        ));


        $ret = callPrivate($c, 'getApiKey', null);
        $this->assertEquals('abc123', $ret);

        $ret = callPrivate($c, 'getApiKey', null);
        $this->assertEquals('abc123', $ret);

        $ret = callPrivate($c, 'getApiKey', null);
        $this->assertEquals(false, $ret);
    }

}