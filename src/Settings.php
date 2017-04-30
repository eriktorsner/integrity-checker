<?php
namespace integrityChecker;

use integrityChecker\Cron\CronExpression;

class Settings
{
    /**
     * The plugin slug
     *
     * @var string
     */
    public $slug;

    /**
     * @var string
     */
    private $cron;

    /**
     * @var int
     */
    private $enableScheduleScans;

    /**
     * @var int
     */
    private $scheduleScanChecksums;

    /**
     * @var int
     */
    private $scheduleScanPermissions;

    /**
     * @var int
     */
    private $scheduleScanSettings;

    /**
     * @var int
     */
    private $enableAlerts;

    /**
     * @var string
     */
    private $alertsEmails;

    /**
     * @var string
     */
    private $fileMasks;

    /**
     * @var string
     */
    private $folderMasks;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var int
     */
    private $followSymlinks;

    /**
     * @var string
     */
    private $fileOwners;

    /**
     * @var string
     */
    private $fileGroups;

    /**
     * @var string
     */
    private $fileIgnoreFolders;

    /**
     * @var array
     */
    private $checksumIgnore;

    /**
     * Keep info about getting and setting all parameters (DRY)
     *
     * @var array
     */
    private $settingParameters;

    /**
     * @var ApiClient;
     */
    private $apiClient;

    /**
     * @var bool
     */
    private $initialized = false;


    /**
     * Settings constructor.
     *
     * @param $slug
     */
    public function __construct($slug, $apiClient)
    {
        $this->slug = $slug;
        $this->apiClient = $apiClient;

        $this->settingParameters = array(
            'cron' => array('option' => 'cron', 'type' => 'string', 'default' => '15 3 * * 1'),
            'enableScheduleScans' => array('option' => 'schedscan_enabled', 'type' => 'bool', 'default' => 0),
            'scheduleScanChecksums' => array('option' => 'schedscan_checksums', 'type' => 'bool', 'default' => 1),
            'scheduleScanPermissions' => array('option' => 'schedscan_permissions', 'type' => 'bool', 'default' => 1),
            'scheduleScanSettings' => array('option' => 'schedscan_settings', 'type' => 'bool', 'default' => 1),
            'enableAlerts' => array('option' => 'alerts_enabled', 'type' => 'bool', 'default' => 0),
            'alertEmails' => array('option' => 'alerts_emails', 'type' => 'string', 'default' => ''),
            'fileMasks' => array('option' => 'file_masks', 'type' => 'string', 'default' => '0644, 0640, 0600'),
            'folderMasks' => array('option' => 'folder_masks', 'type' => 'string', 'default' => '0755, 0750, 0700'),
            'fileOwners' => array('option' => 'file_owners', 'type' => 'string', 'default' => null),
            'fileGroups' => array('option' => 'file_groups', 'type' => 'string', 'default' => null),
            'maxFileSize' => array('option' => 'max_file_size', 'type' => 'num', 'default' => 2),
            'followSymlinks' => array('option' => 'follow_symlinks', 'type' => 'bool', 'default' => 0),
            'checksumIgnore' => array('option' => 'checksum_ignore', 'type' => 'arr', 'default' => array()),
            'fileIgnoreFolders' => array(
                'option' => 'file_ignore_folders',
                'type' => 'string',
                'default' => "wp-content/cache*"
            ),
        );
    }

    /**
     * On demand initialize
     */
    private function initialize()
    {
        foreach ($this->settingParameters as $name => $par) {
            $value = get_option($this->slug . '_' . $par['option'], $par['default']);
            switch ($par['type']) {
                case 'string':
                    $this->$name = $value;
                    break;
                case 'bool':
                    $this->$name = (int)$value;
                    break;
                case 'int':
                    $this->$name = (int)$value;
                    break;
                case 'num':
                    $this->$name = (double)$value;
                    break;
                case 'arr':
                    $this->$name = (array)$value;
            }

            if ($name == 'cron') {
                $this->validateCron();
            }
        }
        $this->initialized = true;
    }

    /**
     * Magic method to access all properties
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($this->settingParameters[$name])) {
            return $this->$name;
        }

        trigger_error('Unknown property Settings::' . $name, E_USER_ERROR);

    }

    /**
     * Store all settings
     *
     * @param $settings
     *
     * @return \WP_Error | null
     */
    public function putSettings($settings)
    {
        foreach ($settings as $settingName => $value) {

            switch ($settingName) {
                case 'cron':
                    try {
                        $cronExpression = CronExpression::factory($value);
                        $this->cron = $value;
                        $this->validateCron();
                        update_option($this->slug . '_cron', $this->cron);

                    } catch (\Exception $e) {
                        return new \WP_Error('fail', 'Invalid cron pattern', array('status' => 400));
                    }
                    break;
                case 'fileMasks':
                case 'folderMasks':
                    $param = $this->settingParameters[$settingName];
                    $this->$settingName = join(', ', $this->validateFileMode($value));
                    update_option($this->slug . '_' . $param['option'], $this->$settingName);
                    break;
                default:
                    if (isset($this->settingParameters[$settingName])) {
                        $param = $this->settingParameters[$settingName];
                        switch ($this->settingParameters[$settingName]['type']) {
                            case 'int':
                            case 'bool':
                                $this->$settingName = (int)$value;
                                break;
                            case 'num':
                                $this->$settingName = (double)$value;
                                break;
                            case 'string':
                                $this->$settingName = $value;
                                break;
                        }
                        update_option(
                            $this->slug . '_' . $param['option'],
                            $this->$settingName
                        );
                    }
            }
        }

        do_action($this->slug . '_ensure_scheduled_tasks');

        $ret = (object)array();
        foreach ($this->settingParameters as $name => $par) {
            $ret->$name = $this->$name;
        }

        return $ret;

    }

    /**
     *
     */
    public function validateCron()
    {
        if ($this->userLevel() == 'registered') {
            try {
            $cronExpression = CronExpression::factory($this->cron);
            $items = explode(' ', $this->cron);
            // We need to ensure that only monthly jobs are set
            if (!is_numeric($items[2])) {
                $items[2] = '1';
            }
            // And we also should make sure that no month or weekday is set
            $items[3] = '*';
            $items[4] = '*';
            $this->cron = join(' ', $items);
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * @param $str
     *
     * @return array
     */
    public function validateFileMode($str)
    {
        $out = array();
        $modes = explode(',', $str);
        foreach ($modes as $mode) {
            $mode = substr('0000' . (int)$mode, -4);
            if ($mode[0] != '0') {
                continue;
            }
            if ((int)$mode[1] > 7) {
                continue;
            }
            if ((int)$mode[2] > 7) {
                continue;
            }
            if ((int)$mode[3] > 7) {
                continue;
            }

            $out[] = $mode;
        }

        return $out;

    }

    /**
     * @return mixed|string
     */
    public function userLevel()
    {
        $ret = get_transient($this->slug . '_accesslevel');

        if (!$ret) {
            $ret = 'anonymous';
            $quotaInfo = $this->apiClient->getQuota();
            $ret = get_transient($this->slug . '_accesslevel');
        }

        return $ret;
    }

    /**
     * @param $emails
     *
     * @return bool
     */
    public function testEmail($emails)
    {
        do_action($this->slug . '_send_test_email');
        return true;
    }

}

