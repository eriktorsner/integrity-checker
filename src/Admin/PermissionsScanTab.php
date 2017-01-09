<?php
namespace integrityChecker\Admin;

/**
 * Class PermissionsScanTab
 * @package integrityChecker\Admin
 */
class PermissionsScanTab extends BaseTab
{
    /**
     * PermissionsScanTab constructor.
     *
     */
    public function __construct()
    {
        $this->tabId = 'permissionscan';
        $this->name = __('Permissions','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
        include __DIR__ . '/views/PermissionsScanResults.php';
    }
}