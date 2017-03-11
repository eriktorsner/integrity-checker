<?php
namespace integrityChecker;

use integrityChecker\Cron\CronExpression;

class Settings
{
    /**
     * @var string
     */
    public $cron;

    /**
     * @var int
     */
    public $enableScheduleScans;

    /**
     * @var int
     */
    public $scheduleScanChecksums;

    /**
     * @var int
     */
    public $scheduleScanPermissions;

    /**
     * @var int
     */
    public $scheduleScanSettings;

    /**
     * @var int
     */
    public $enableAlerts;

    /**
     * @var string
     */
    public $alertsEmails;

    /**
     * @var string
     */
    public $fileMasks;

    /**
     * @var string
     */
    public $folderMasks;

    /**
     * @var int
     */
    public $maxFileSize;

    /**
     * @var string
     */
    public $fileOwners;

    /**
     * @var string
     */
    public $fileGroups;

    /**
     * The plugin slug
     *
     * @var string
     */
    public $slug;

    /**
     * Keep info about getting and setting all parameters (DRY)
     *
     * @var array
     */
    private $settingParameters;

    /**
     * Settings constructor.
     *
     * @param $slug
     */
    public function __construct($slug)
    {
        $this->slug = $slug;

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
        );

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
            }
        }
    }

    /**
     * Store all settings
     *
     * @param $settings
     *
     * @return \WP_Error
     */
    public function putSettings($settings)
    {
        foreach ($settings as $settingName => $value) {

            switch ($settingName) {
                case 'cron':
                    try {
                        $cronExpression = CronExpression::factory($value);
                        $this->cron = $value;
                        update_option($this->slug . '_cron', $this->cron);
                        do_action($this->slug . '_ensure_scheduled_tasks');

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

        return $this;
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

