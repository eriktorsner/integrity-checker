<?php
namespace integrityChecker;

use integrityChecker\Cron\FieldFactory;

/**
 * Class Rest
 *
 * Manages the REST endpoints for the Integrity Checker plugin
 *
 * @package integrityChecker
 */
class Rest
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var FileDiff
     */
    private $fileDiff;

    /**
     * Rest constructor.
     *
     * @param $settings
     * @param $apiClient
     * @param $process
     * @param $fileDiff
     */
    public function __construct($settings, $apiClient, $process, $fileDiff)
    {
        $this->settings = $settings;
        $this->apiClient = $apiClient;
        $this->process = $process;
        $this->fileDiff = $fileDiff;
    }

    /**
     * Register all REST endpoints
     */
    public function registerRestEndpoints()
    {

        $typeDef = '(?P<type>[a-zA-Z0-9-]+)';
        $slugDef = '(?P<slug>[a-zA-Z0-9-]+)';
        $nameDef = '(?P<name>[a-zA-Z0-9-]+)';
        $emailDef = '(?P<emails>.*)';

        /** *********************************************
         *
         * User and quota
         *
         ***********************************************/
        register_rest_route('integrity-checker/v1', 'quota', array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $ret =  $this->apiClient->getQuota();

                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        register_rest_route('integrity-checker/v1', 'apikey', array(
            'methods' => array('PUT'),
            'callback' => function($request) {
                $apiKey = $request->get_param('apiKey');
                $ret =  $this->apiClient->verifyApiKey($apiKey);

                return is_wp_error($ret)?
                    $this->errSend($ret):
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        register_rest_route('integrity-checker/v1', 'userdata', array(
            'methods' => array('PUT'),
            'callback' => function($request) {
                $email = $request->get_param('email');
                $ret =  $this->apiClient->registerEmail($email);

                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));


        /** *********************************************
         *
         * Processes
         *
         ***********************************************/
        register_rest_route('integrity-checker/v1', 'process/status', array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $ret = $this->process->status($request);
                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        register_rest_route('integrity-checker/v1', "process/status/$nameDef", array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $ret = $this->process->status($request);
                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        register_rest_route('integrity-checker/v1', "process/status/$nameDef", array(
            'methods' => array('PUT'),
            'callback' => function($request) {
                $ret = $this->process->update($request);
                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));


        /** *********************************************
         *
         * Test results
         *
         ***********************************************/
        register_rest_route('integrity-checker/v1', "testresult/$nameDef", array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $ret = $this->process->getTestResults($request);

                $escape = filter_var($request->get_param('esc'), FILTER_VALIDATE_BOOLEAN);
                if ($escape) {
                    $this->escapeObjectStrings($ret);
                }

                return is_wp_error($ret)?
                    $ret:
                    $this->jSend($ret);
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        /** *********************************************
         *
         * File diff
         *
         ***********************************************/
        register_rest_route('integrity-checker/v1', "diff/$typeDef/$slugDef", array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $type = $request->get_param('type');
                $slug = $request->get_param('slug');
                $file = $request->get_header('X-Filename');

                $ret = $this->fileDiff->getDiff($type, $slug, $file);

                if (is_object($ret)) {
                    return $ret;
                } else {
                    return $ret;
                }
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        /** *********************************************
         *
         * Settings
         *
         ***********************************************/
        register_rest_route('integrity-checker/v1', "testemail/$emailDef", array(
            'methods' => array('GET'),
            'callback' => function($request) {
                $emails = $request->get_param('emails');
                $ret = $this->settings->testEmail($emails);
                return $ret;
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));

        register_rest_route('integrity-checker/v1', "settings", array(
            'methods' => array('PUT'),
            'callback' => function($request) {
                $strBody = $request->get_body();
                $newSettings = json_decode($strBody);
                if ($newSettings) {
                    $ret = $this->settings->putSettings($newSettings);

                    return is_wp_error($ret)?
                        $ret:
                        $this->jSend($ret);
                }
                return new \WP_Error(
                    'fail',
                    'Invalid request body',
                    array('status' => 400)
                );
            },
            'permission_callback' => array($this, 'checkPermissions'),
        ));
    }


    /**
     * Ensure the client is authorized to use this API
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function checkPermissions($request)
    {
	    if ($nonce = $request->get_header('X-WP-NONCE')) {
			if (wp_verify_nonce($nonce, 'wp_rest')) {
			    return true;
		    }
	    }

        return true;
    }

    /**
     * Ensure error object data property is set
     *
     * @param \WP_Error $error
     *
     * @return \WP_Error
     */
    private function errSend($error)
    {
        $errorData = $error->get_error_data();
        if (is_array($errorData ) && isset( $errorData['status'] ) ) {
            return $error;
        }

        if (count($error->errors) > 0) {
            $lastError = end($error->errors);
            $status = key($error->errors);
            $error->add_data(array('status' => $status, 'message' => $lastError[0]), $status);
        }

        return $error;
    }

    /**
     * Wrap the response in a JSend struct
     *
     * @param $response
     * @return object
     */
    private function jSend($response)
    {
        return (object)array(
            'code' => 'success',
            'message' => null,
            'data' => $response,
        );
    }

    /**
     * Walk through the object and ensure all strings are escaped
     *
     * @param $obj
     */
    private function escapeObjectStrings(&$obj)
    {
        if (!$obj) {
            return;
        }
        foreach ($obj as $key => &$item) {
            if (is_string($item)) {
                $item = esc_html($item);
            }

            if (is_object($item) || is_array($item)) {
                $this->escapeObjectStrings($item);
            }
        }
    }
}