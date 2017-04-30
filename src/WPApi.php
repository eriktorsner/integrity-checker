<?php
namespace integrityChecker;

/**
 * Class WPApi
 *
 * Encapsules some WordPress functions
  *
 * @package integrityChecker
 */
class WPApi
{
    public function getThemesPath()
    {

    }
    public function getPluginsPath()
    {
        return WP_PLUGIN_DIR;
    }

    public function getAbsPath()
    {
        return ABSPATH;
    }

    /**
     * Return the current WordPress version
     *
     * @return string
     */
    public function getWpVersion()
    {
        global $wp_version;
        return $wp_version;
    }
}