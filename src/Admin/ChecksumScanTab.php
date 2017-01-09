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
     */
    public function __construct()
    {
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