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
     * @var \integrityChecker\Settings
     */
    public $settings;

    /**
     * BaseTab constructor.
     *
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

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
     * Render the content of the tab
     * (You're supposed to override this)
     */
    public function render()
    {
    }

    /**
     * Return an array of scripts that needs to be
     * enqueued for this tab
     */
    public function getScripts()
    {
       return array();
    }

    /**
     * Return an array of styles that needs to be
     * enqueued for this tab
     */
    public function getStyles()
    {
        return array();
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