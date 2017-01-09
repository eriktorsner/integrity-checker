<?php
namespace integrityChecker\Admin;

/**
 * Class GeneralOptions
 * @package integrityChecker\Admin
 */
class GeneralOptions
{
    /**
     * ChecksumScanTab constructor.
     *
     */
    public function __construct()
    {
        $this->tabId = 'generaloptions';
        $this->name = __('Options','integrity-checker');
    }

    /**
     * Render
     */
    public function render()
    {
    }
}