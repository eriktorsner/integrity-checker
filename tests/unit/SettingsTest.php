<?php
namespace integrityChecker;

class SettingsTest extends \PHPUnit_Framework_TestCase
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
        $settingParameters = array(
            'cron' => array('option' => 'cron', 'type' => 'string', 'default' => '15 3 * * 1', 'bad' => 'asds'),
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
            'followSymlinks' => array('option' => 'follow_symlinks', 'type' => 'num', 'default' => 0),
            'checksumIgnore' => array('option' => 'checksum_ignore', 'type' => 'arr', 'default' => array()),
        );

        foreach ($settingParameters as $parameter) {
            \WP_Mock::userFunction('get_option', array(
                'args' => array('fooslug_' . $parameter['option'], $parameter['default']),
                'times' => 2,
                'return' => $parameter['default'],
            ));
        }

        \WP_Mock::userFunction('get_transient', array(
            'args' => array('fooslug_accesslevel'),
            'times' => 3,
            'return_in_order' => array('anonymous', false),
        ));

        \WP_Mock::userFunction('set_transient', array(
            'args' => array('fooslug_accesslevel', 'anonymous', 86400),
            'times' => 1,
        ));

        $apiClient = new \MockApiClient(array());
        $settings = new Settings('fooslug', $apiClient);
        foreach ($settingParameters as $name => $parameter) {
            $this->assertEquals($parameter['default'], $settings->$name);
        }

        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $apiClient);
        foreach ($settingParameters as $name => $parameter) {
            $this->assertEquals($parameter['default'], $settings->$name);
        }
    }

    public function testPutSettings()
    {
        $mockApiClient = new \MockApiClient(array());
        $settings = new Settings('fooslug', $mockApiClient);
        $newData = array(
            'cron' => array('', '* * * * *', '* * 3 *'),
            'enableScheduleScans' => array('', null, false, 1, 0),
            'scheduleScanChecksums' => array('', null, false, 1, 0),
            'scheduleScanPermissions' => array('', null, false, 1, 0),
            'scheduleScanSettings' => array('', null, false, 1, 0),
            'enableAlerts' => array('', null, false, 1, 0),
            'alertEmails' => array('', 'foobar@example.com'),
            'fileMasks' => array('', '0777', '07', 77, 'asdasd'),
            'folderMasks' => array('', '0777', '07', 77, 'asdasd'),
            'fileOwners' => array('a,b,c', ',a',),
            'fileGroups' => array('a,b,c', ',a',),
            'maxFileSize' => array('1M', '10', 10, null),
            'followSymlinks' => array('1', '0', 'abc', null),
            'checksumIgnore' => array(null, array()),
        );

        $badCronPatterns = 2;
        $totalCount = 0;
        foreach ($newData as $value) {
            $totalCount += count($value);
        }

        \WP_Mock::userFunction('update_option', array(
            'times' => $totalCount - $badCronPatterns,
        ));

        foreach ($newData as $name => $values) {
            foreach ($values as $value) {
                $data = (object)array($name => $value);
                $settings->putSettings($data);
            }
        }
    }

    public function testValidateFileMode()
    {
        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $dummy);
        $tests = array(
            array('0000', array('0000')),
            array('0008', array()),
            array('999', array()),
            array('0788', array()),
            array('777,222', array('0777', '0222')),
            array('7777777,9888888899999', array()),
        );

        foreach ($tests as $pattern) {
            $result = $settings->validateFileMode($pattern[0]);
            $this->assertEquals($pattern[1], $result);
        }
    }

    public function testValidateCron()
    {
        $settingParameters = array(
            'cron' => array('option' => 'cron', 'type' => 'string', 'default' => '15 3 * * 1', 'bad' => 'asds'),
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
            'followSymlinks' => array('option' => 'follow_symlinks', 'type' => 'num', 'default' => 0),
            'checksumIgnore' => array('option' => 'checksum_ignore', 'type' => 'arr', 'default' => array()),
        );

        foreach ($settingParameters as $parameter) {
            \WP_Mock::userFunction('get_option', array(
                'args' => array('fooslug_' . $parameter['option'], $parameter['default']),
                'times' => 3,
                'return' => $parameter['default'],
            ));
        }

        \WP_Mock::userFunction('get_transient', array(
            'args' => array('fooslug_accesslevel'),
            'times' => 3,
            'return_in_order' => array('anonymous', 'registered', 'paid'),
        ));

        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $dummy);
        $this->assertEquals('15 3 * * 1', $settings->cron);

        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $dummy);
        $this->assertEquals('15 3 1 * *', $settings->cron);

        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $dummy);
        $this->assertEquals('15 3 * * 1', $settings->cron);
    }

    public function testTestEmail()
    {
        $dummy = new \stdClass();
        $settings = new Settings('fooslug', $dummy);
        $settings->testEmail('foobar@example.com');
    }
}

