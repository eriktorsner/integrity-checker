<?php
namespace integrityChecker;

/**
 * Class FileDiff
 * @package WPChecksum
 */
class FileDiff
{

    /**
     * Compare a local file with the original file
     * fetched via the API
     *
     * @param string $type
     * @param string $slug
     * @param string $file
     *
     * @return string|\WP_Error
     */
    public function getDiff($type, $slug, $file)
    {
        $files = false;
        switch ($type) {
            case 'core':
                $files = $this->getCoreFiles($file);
                break;
            case 'plugin':
                $files = $this->getPluginFiles($slug, $file);
                if (!$files) {
                    return new \WP_Error(400, 'Local plugin not found');
                }
                break;
            case 'theme':
                $files = $this->getThemeFiles($slug, $file);
                if (!$files) {
                    return new \WP_Error(400, 'Local theme not found');
                }
                break;
        }

        if ($files->remote['response']['code'] != 200) {
            if (strlen($files->remote['body']) > 0) {
                $body = json_decode($files->remote['body']);
                return new \WP_Error($body->status, $body->message);
            }
            return new \WP_Error(
                $files->remote['response']['code'],
                $files->remote['response']['message']
            );
        }

        if (strlen($files->local) > 0 || strlen($files->remote['body']) > 0) {
            $html = wp_text_diff(
                $files->remote['body'],
                $files->local,
                array(
                    'title_left' => __('Original', 'integrity-checker'),
                    'title_right' => __('Local', 'integrity-checker'),
                )
            );

            return $html;
        }
        return new \WP_Error(400, 'File not found');

    }

    /**
     * Get the local and remote (original) version of
     * a file from Core
     *
     * @param string $file
     *
     * @return object
     */
    private function getCoreFiles($file)
    {
        global $wp_version;
        require_once ABSPATH . WPINC . '/version.php';

        $localFile = ABSPATH . '/' . $file;
        $localFileContent = file_get_contents($localFile);

        $client = new ApiClient();
        $remoteFile = $client->getFile('core', 'core', $wp_version, $file);

        return (object)array(
            'local' => $localFileContent,
            'remote' => $remoteFile
        );
    }

    /**
     * Get the local and remote (original) version of
     * a file from a plugin
     *
     * @param string $slug
     * @param string $file
     *
     * @return bool|object
     */
    private function getPluginFiles($slug, $file)
    {
        if ($info = $this->getPluginInfo($slug)) {
            $localFile        = $info->root . '/' . $file;
            $localFileContent = file_get_contents($localFile);

            $client     = new ApiClient();
            $remoteFile = $client->getFile('plugin', $slug, $info->version, $file);

            return (object)array(
                'local'  => $localFileContent,
                'remote' => $remoteFile
            );
        }

        return false;

    }

    /**
     * Get the local and remote (original) version of
     * a file from a theme
     *
     * @param string $slug
     * @param string $file
     *
     * @return bool|object
     */
    private function getThemeFiles($slug, $file)
    {
        if ($info = $this->getThemeInfo($slug)) {
            $localFile = $info->root. '/' . $file;
            $localFileContent = file_get_contents($localFile);

            $client = new ApiClient();
            $remoteFile = $client->getFile('theme', $slug, $info->version, $file);

            return (object)array(
                'local'  => $localFileContent,
                'remote' => $remoteFile
            );
        }

        return false;
    }

    /**
     * Get details about the request plugin
     *
     * @param string $slug
     *
     * @return bool|object
     */
    private function getPluginInfo($slug)
    {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
        $plugins = get_plugins();
        foreach ($plugins as $id => $plugin) {
            $parts = explode('/', $id);
            $pluginSlug = $parts[0];
            if ($slug == $pluginSlug) {
                return (object)array(
                    'slug' => $slug,
                    'version' => $plugin['Version'],
                    'root' => WP_PLUGIN_DIR . '/' . $slug,
                );
            }
        }

        return false;
    }

    /**
     * Get details about the request theme
     *
     * @param string $slug
     *
     * @return bool|object
     */
    private function getThemeInfo($slug)
    {
        require_once ABSPATH.'/wp-admin/includes/file.php';
        $themes = wp_get_themes();
        foreach ($themes as $themeSlug => $theme) {
            if ($slug == $themeSlug) {
                $version = $theme->get('Version');
                return (object)array(
                    'slug' => $slug,
                    'version' => $version,
                    'root' => $theme->theme_root . '/' . $theme->stylesheet,
                );
            }
        }

        return false;
    }
}