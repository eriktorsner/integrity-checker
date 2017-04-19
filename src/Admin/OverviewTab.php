<?php
namespace integrityChecker\Admin;

/**
 * Class GeneralOptions
 * @package integrityChecker\Admin
 */
class OverviewTab extends BaseTab
{
    /**
     * ChecksumScanTab constructor.
     *
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

        $this->tabId = 'generaloptions';
        $this->name = __('Overview','integrity-checker');
    }

    /**
     * @return array
     */
    public function getScripts()
    {
        return array(
            array(
                'id' => $this->tabId,
                'file' => '/js/adminOverview.js',
                'deps' => array()
            ),
        );
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/Overview.php';
    }
}