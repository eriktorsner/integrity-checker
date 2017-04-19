<?php
namespace integrityChecker\Admin;

/**
 * Class SettingsScanTab
 * @package integrityChecker\Admin
 */
class SettingsScanTab extends BaseTab
{

    /**
     * SettingsScanTab constructor.
     *
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->tabId = 'settingscscan';
        $this->name = __('Misc checks','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/SettingsScanResults.php';
    }
}