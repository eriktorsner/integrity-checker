<?php
namespace integrityChecker\Admin;

/**
 * Class BaseTab
 * @package integrityChecker\Admin
 */
class BaseTab
{
    /**
     * @var string
     */
    public $tabId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $active = false;

    /**
     * The URL for this tab
     * @return string
     */
    public function getUrl()
    {
        return '?page=integrity-checker_options' .
               '?tab=' . $this->tabId;
    }

    /**
     * Renter the content of the tab
     * (You're supposed to override this)
     */
    public function render()
    {
    }

    /**
     * Called when rendering the tab header
     * to determine which tabs is active
     * @return string
     */
    public function display()
    {
        return $this->active? 'block':'none';
    }
}