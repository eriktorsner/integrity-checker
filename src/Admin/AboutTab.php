<?php
namespace integrityChecker\Admin;

/**
 * Class AboutTab
 * @package integrityChecker\Admin
 */
class AboutTab extends BaseTab
{
    /**
     * AboutTab constructor.
     *
     */
    public function __construct()
    {
        $this->tabId = 'about';
        $this->name = __('About','integrity-checker');
    }

    public function render()
    {
        include __DIR__ . '/views/About.php';
    }
}