<?php
namespace integrityChecker\Admin;

/**
 * Class PermissionsScanTab
 * @package integrityChecker\Admin
 */
class FilesScanTab extends BaseTab
{
    /**
     * PermissionsScanTab constructor.
     *
     */
    public function __construct()
    {
        $this->tabId = 'files';
        $this->name = __('Files','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/FilesScanResults.php';
    }
}