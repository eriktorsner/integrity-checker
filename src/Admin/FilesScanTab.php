<?php
namespace integrityChecker\Admin;

/**
 * Class PermissionsScanTab
 * @package integrityChecker\Admin
 */
class FilesScanTab extends BaseTab
{
    /**
     * FilesScanTab constructor.
     *
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

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