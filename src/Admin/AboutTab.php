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
     * @param \integrityChecker\Settings $settings
     */
    public function __construct($settings)
    {
        parent::__construct($settings);

        $this->tabId = 'about';
        $this->name = __('About','integrity-checker');
    }

    public function getScripts()
    {
        return array(
            array(
                'id' => $this->tabId,
                'file' => '/js/adminAbout.js',
                'deps' => array()
            ),
        );
    }

    public function render()
    {
        include __DIR__ . '/views/About.php';
    }
}