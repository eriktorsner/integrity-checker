<?php
namespace integrityChecker\Admin;

/**
 * Class SettingsScanTab
 * @package integrityChecker\Admin
 */
class SettingsScanTab extends BaseTab
{

    /**
     * AboutTab constructor.
     *
     */
    public function __construct()
    {
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