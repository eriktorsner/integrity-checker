<?php
namespace integrityChecker\Admin;

/**
 * Class UpgradeTab
 * @package integrityChecker\Admin
 */
class UpgradeTab extends BaseTab
{
    /**
     * UpgradeTab constructor.
     *
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

        $this->tabId = 'upgrade';
        $this->name = __('Upgrade','integrity-checker');
    }

    public function getScripts()
    {
        return array();
    }

    public function render()
    {
        include __DIR__ . '/views/Upgrade.php';
    }
}