<?php
namespace integrityChecker;

class SettingsTest extends \PHPUnit_Framework_TestCase
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
        );

        foreach ($settingParameters as $parameter) {
            \WP_Mock::userFunction('get_option', array(
                'args' => array('fooslug_' . $parameter['option'], $parameter['default']),
                'times' => 1,
                'return' => $parameter['default'],
            ));
        }

        $settings = new Settings('fooslug');

        foreach ($settingParameters as $name => $parameter) {
            $this->assertEquals($parameter['default'], $settings->$name);
        }
    }

    public function testPutSettings()
    {
        $settings = new Settings('fooslug');
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
        $settings = new Settings('fooslug');
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

    public function testTestEmail()
    {
        $settings = new Settings('fooslug');
        $settings->testEmail('foobar@example.com');
    }
}

