<?php
namespace integrityChecker;

/**
 * Class AdminUIHooks
 *
 * @package integrityChecker
 */
class AdminUIHooks
{
    /**
     * @var null
     */
    private $pluginInfo = null;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var State
     */
    private $state;

    /**
     * AdminUIHooks constructor.
     *
     * @param $settings
     * @param $state
     */
    public function __construct($settings, $state)
    {
        $this->settings= $settings;
        $this->state = $state;
    }

    /**
     * Register hooks
     */
    public function register()
    {
        global $pagenow;

        add_action('load-plugins.php', array($this, 'loadPlugins'));

        $baseName = plugin_basename(dirname(__DIR__) . '/integrity-checker.php');
        add_filter('plugin_action_links_' . $baseName, array($this, 'pluginActionLinks'), 10, 1);

        if ($pagenow == 'update.php') {
            add_filter('site_transient_update_plugins', array($this, 'modifyPluginsTransient'), 99, 2);
        }
    }

    /**
     * Determine which plugins that have issues and hook them to after_plugin_row
     */
    public function loadPlugins()
    {
        $slugs = array();
        $allPlugins = get_site_transient('update_plugins');
        if ($allPlugins && isset($allPlugins->checked)) {
            foreach ($allPlugins->checked as $key => $value) {
                $parts = explode('/', $key);
                $slug = $parts[0];
                $slugs[$slug] = $key;
            };
        }

        $checksumResults = $this->state->getTestResult('checksum');
        if ($checksumResults && isset($checksumResults['plugins'])) {
            foreach ($checksumResults['plugins'] as $plugin) {
                if ($plugin->hardIssues > 0) {
                    if (isset($slugs[$plugin->slug]) && !isset($allPlugins->response[$slugs[$plugin->slug]])) {
                        add_action("after_plugin_row_{$slugs[$plugin->slug]}", array($this, 'offerUpdate'), 10, 3);
                    }
                }
            }
        }
    }

    /**
     * @param $pluginFile
     * @param $pluginData
     * @param $status
     */
    public function offerUpdate($pluginFile, $pluginData, $status)
    {

        $pluginsAllowedtags = array(
            'a'       => array('href' => array(), 'title' => array()),
            'abbr'    => array('title' => array()),
            'acronym' => array('title' => array()),
            'code'    => array(),
            'em'      => array(),
            'strong'  => array(),
        );

        $current = get_site_transient('update_plugins');
        $response = $current->checked[$pluginFile];

        $pluginName   = wp_kses($pluginData['Name'], $pluginsAllowedtags);
        $slug = $pluginData['slug'];
        $upgradeUrl = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=') .
                                   $pluginFile, 'upgrade-plugin_' . $pluginFile);
        include __DIR__ . '/Admin/views/PluginUpdateAlert.php';
    }

    /**
     * @param $links
     *
     * @return array
     */
    public function pluginActionLinks($links)
    {
        $url = admin_url('tools.php?page=' . $this->settings->slug . '_options');

        return array_merge(
            $links,
            array('settings' => '<a href="' . $url . '">Settings</a>')
        );
    }

    /**
     * @param $value
     * @param $transient
     *
     * @return mixed
     */
    public function modifyPluginsTransient($value, $transient)
    {
        // Modify the transient to slip by WP's same version check
        $pluginFile = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']): '';
        if (strlen($pluginFile) && !isset($value->response[$pluginFile])) {
            $value->response[$pluginFile] = $this->getPluginInfo($pluginFile);
        }

        return $value;
    }

    /**
     * @param $pluginFile
     *
     * @return null|object
     */
    private function getPluginInfo($pluginFile)
    {
        require_once ABSPATH.'/wp-admin/includes/plugin-install.php';
        if (is_null($this->pluginInfo)) {
            $parts = explode('/', $pluginFile);
            $slug = $parts[0];
            $pluginApi = plugins_api(
                'plugin_information',
                array( 'slug' => $slug,
                       'fields' => array('sections' => false, 'compatibility' => false, 'tags' => false)
                )
            );
            $this->pluginInfo = (object)array(
                'slug'        => $slug,
                'new_version' => $pluginApi->version,
                'package'     => $pluginApi->download_link
            );
        }

        return $this->pluginInfo;
    }
}



