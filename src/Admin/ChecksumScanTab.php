<?php
namespace integrityChecker\Admin;

/**
 * Class ChecksumScanTab
 * @package integrityChecker\Admin
 */
class ChecksumScanTab extends BaseTab
{
    /**
     * ChecksumScanTab constructor.
     *
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

        $this->tabId = 'checksumscan';
        $this->name = __('Checksum','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/ChecksumScanResults.php';
    }
}