<?php
namespace integrityChecker\Admin;

/**
 * Class GeneralOptions
 * @package integrityChecker\Admin
 */
class SettingsTab extends BaseTab
{
    /**
     * ChecksumScanTab constructor.
     *
     */
    public function __construct()
    {
        $this->tabId = 'settings';
        $this->name = __('Settings','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/Settings.php';
    }

    public function getScripts()
    {
        return array(
            array(
                'id' => $this->tabId,
                'file' => '/js/adminSettings.js',
                'deps' => array()
            ),
        );
    }
}