<?php
namespace integrityChecker;

/**
 * Class ApiClient
 * @package integrityChecker
 */
class ApiClient
{
    const NO_APIKEY = 1;
    const INVALID_APIKEY = 2;
    const RATE_LIMIT_EXCEEDED = 3;
    const RESOURCE_NOT_FOUND = 4;
    const INVALID_EMAIL = 5;
    const EMAIL_IN_USE = 6;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var object
     */
    private $lastError;

    /**
     * ApiClient constructor.
     */
    public function __construct()
    {
        $this->baseUrl = 'https://api.wpessentials.io/v1';
        if (defined('INTEGRITY_CHECKER_URL')) {
            $this->baseUrl = INTEGRITY_CHECKER_URL;
        }
        $this->lastError = 0;
    }

    /**
     * @return int|object
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get api rate limit quota for the current user
     *
     * @param string|null $apiKey
     *
     * @return null|object
     */
    public function getQuota($apiKey = null)
    {
        $this->lastError = 0;
        if (!$apiKey) {
            $apiKey = $this->getApiKey();
        }

        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
        }

        $url = join('/', array($this->baseUrl, 'quota'));
        $args = array('headers' => $this->headers($apiKey));
        $ret = wp_remote_get($url, $args);
        $this->updateSiteId($ret);

        $out = null;

        if (is_wp_error($ret)) {
            return $out;
        }

        switch ($ret['response']['code']) {
            case 401:
                $out = null;
                $this->lastError = self::INVALID_APIKEY;
                break;
            case 200:
                $out = json_decode($ret['body']);
                $this->parseQuota($out);
                break;
        }

        return $out;
    }

    /**
     * @param object $quota
     */
    private function parseQuota($quota)
    {
        if ($quota->hourlyLimit == -1 && $quota->dailyLimit == -1 && $quota->monthlyLimit == -1) {
            $quota->rateLimit = 'Unlimited';
            $quota->resetIn = '';
            $quota->currentUsage = '';
        }

        if ($quota->rateLimit != 'Unlimited') {
            $rateLimit = array();
            $resetIn = array();
            $currentUsage = array();
            if ($quota->hourlyLimit > 0) {
                $rateLimit[] = $quota->hourlyLimit . '/hour';
                $resetIn[] = $quota->hourlyResetIn . ' s';
                $currentUsage[] = $quota->hourlyCurrent;
            }
            if ($quota->dailyLimit > 0) {
                $rateLimit[] = $quota->dailyLimit . '/day';
                $resetIn[] = floor($quota->dailyResetIn/3600) . ' h';
                $currentUsage[] = $quota->dailyCurrent;
            }
            if ($quota->monthlyLimit > 0) {
                $rateLimit[] = $quota->monthlyLimit . '/month';
                $resetIn[] = floor($quota->monthlyResetIn/86400) . ' days';
                $currentUsage[] = $quota->monthlyCurrent;
            }

            $quota->rateLimit = join(' + ', $rateLimit);
            $quota->resetIn = join(' / ', $resetIn);
            $quota->currentUsage = join(' / ', $currentUsage);
        }

        $quota->siteLimit = 'Unlimited';
        if ($quota->maxSites > -1) {
            $quota->siteLimit = $quota->maxSites . ' sites';
        }

    }

    /**
     * Verify that an Apikey is valid
     *
     * @param string $apiKey
     *
     * @return object|\WP_Error
     */
    public function verifyApiKey($apiKey)
    {
        $ret = $this->getQuota($apiKey);
        if ($this->lastError === 0) {
            update_option('wp_checksum_apikey', $apiKey);
            $ret->message = __('API key updated', 'integrity-checker');
            return $ret;
        }

        return new \WP_Error(
            400,
            __('API key verification failed. Key not updated', 'integrity-checker')
        );
    }

    /**
     * Register email address with backend
     * (increases quota)
     *
     * @param string $email
     *
     * @return object|\WP_Error
     */
    public function registerEmail($email)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
            return null;
        }

        $url = join('/', array($this->baseUrl, 'userdata'));
        $args = array(
            'headers' => $this->headers($apiKey, array('Content-Type' => 'application/json')),
            'body' => json_encode(array(
                'email' => $email,
                'host' => get_site_url(),
            )),
        );

        $args = http_build_query($args);
        $out = wp_remote_post($url, $args);
        $this->updateSiteId($out);

        if (is_wp_error($out)) {
            return $out;
        }

        switch ($out['response']['code']) {
            case 401:
                $this->lastError = self::INVALID_APIKEY;
                break;
        }

        $out = json_decode($out['body']);

        return $out;

    }

    /**
     * @param $type
     * @param $slug
     * @param $version
     * @return null|object
     */
    public function getChecksums($type, $slug, $version)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
            return null;
        }

        $url = join('/', array($this->baseUrl, 'checksum', $type, $slug, $version));
        $args = array('headers' => $this->headers($apiKey));
        $out = wp_remote_get($url, $args);
        $this->updateSiteId($out);

        if (is_wp_error($out)) {
            return null;
        }

        switch ($out['response']['code']) {
            case 401:
                $out = null;
                $this->lastError = self::INVALID_APIKEY;
                break;
            case 404:
                $this->lastError = self::RESOURCE_NOT_FOUND;
                $out = null;
                break;
            case 429:
                $out = null;
                $this->lastError = self::RATE_LIMIT_EXCEEDED;
                break;
            case 200:
                $out = json_decode($out['body']);
                if (!isset($out->checksums))  {
                    $out->checksums = array();
                }
                $out->checksums = (array)$out->checksums;
                break;
        }

        return $out;
    }

    /**
     * Get original source file of a plugin/theme
     *
     * @param string $type
     * @param string $slug
     * @param string $version
     * @param string $file
     *
     * @return array|null|\WP_Error
     */
    public function getFile($type, $slug, $version, $file)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
            return null;
        }

        $url = join('/', array($this->baseUrl, 'file', $type, $slug, $version));
        $args = array(
            'headers' => $this->headers($apiKey, array('X-Filename' => $file)),
        );

        $out = wp_remote_get($url, $args);
        $this->updateSiteId($out);

        return $out;
    }

    /**
     * @param $emails
     *
     * @return object
     */
    public function testAlertEmails($emails)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
            return null;
        }

        $emails = explode(",", $emails);

        $args = array(
            'headers' => $this->headers($apiKey, array('Content-Type' => 'application/json')),
            'body'    => json_encode(array(
                'email' => $emails,
            )),
        );

        $url = join('/', array($this->baseUrl, 'alerts', 'test'));
        $out = wp_remote_post($url, $args);

        return $out;
    }

    /**
     * Read an API key from the WordPress DB
     *
     * If no API key is found, attempt to create a anonymous account
     * at wpessentials.io and store credentials in the WP db
     *
     * @return bool|string
     */
    private function getApiKey()
    {
        // does this WP installation have a key?
        $apiKey = get_option('wp_checksum_apikey', false);
        if ($apiKey ) {
            return $apiKey;
        }

        // No? Let's see if we can create a key via the API
        $url = join('/', array($this->baseUrl, 'anonymoususer'));
        $args = array(
            'headers' => array(
                'X-Checksum-Client' => 'integrity-checker; ' . INTEGRITY_CHECKER_VERSION,
            ),
        );
        $out = wp_remote_post($url);
        $this->updateSiteId($out);
        if ($out['response']['code'] == 200) {
            $ret = json_decode($out['body']);
            $apiKey = base64_encode($ret->user . ':' . $ret->secret);
            update_option('wp_checksum_apikey', $apiKey);

            return $apiKey;
        }

        return false;
    }

    /**
     * Check if the server wants us to set a new siteid
     *
     * @param $response
     */
    private function updateSiteId($response)
    {
        if (is_wp_error($response)) {
            return;
        }

        if (!isset($response['http_response'])) {
            return;

        }
        $objHeaders = $response['http_response'];
        $headers = $objHeaders->get_headers()->getAll();

        if (isset($headers['x-checksum-site-id'])) {
            $currentId = get_option('wp_checksum_siteid', false);
            if ($currentId != $headers['x-checksum-site-id']) {
                update_option('wp_checksum_siteid', $headers['x-checksum-site-id']);
                // copy site settings to new site...
            }
        }
    }

    /**
     * Prepare standard headers
     *
     * @param $apiKey
     * @param $arr
     *
     * @return array
     */
    private function headers($apiKey, $arr = array())
    {
        $ret = array(
            'Authorization' => $apiKey,
            'X-Checksum-Client' => 'integrity-checker; ' . INTEGRITY_CHECKER_VERSION,
        );
        $siteId = get_option('wp_checksum_siteid', false);
        if ($siteId) {
            $ret['X-Checksum-Site-Id'] = $siteId;
        }

        foreach ($arr as $key => $value) {
            $ret[$key] = $value;
        }

        return $ret;
    }
}