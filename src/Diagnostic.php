<?php
namespace integrityChecker;

class Diagnostic
{
	public function get()
	{
		$ret = "Dianostics\n";
		$ret .= $this->wordPress() . "\n";
		$ret .= $this->webServer() . "\n";
		$ret .= $this->database() . "\n";
		$ret .= $this->installedThemes() . "\n";
		$ret .= $this->installedPlugins() . "\n";
		$ret .= $this->phpExtensions() . "\n";

		return $ret;
	}

	private function wordPress()
	{
		$ret = "\n*** WordPress ***\n";
		$ret .= "Permalink structure: " . get_option('permalink_structure') . "\n";
		$ret .= "Category base: " . get_option('category_base') . "\n";
		$ret .= "Tag base: " . get_option('tag_base') . "\n";
		$ret .= "WP Max Memory Limit: " . WP_MAX_MEMORY_LIMIT."\n";
		$ret .= "WP Memory Limit: " . WP_MEMORY_LIMIT."\n";
		$ret .= "WP Locale:" . esc_html(get_locale()) . "\n";


		$ret .= $this->wpDefine('WP_CACHE');
		$ret .= $this->wpDefine('WP_DEBUG');
		$ret .= $this->wpDefine('WP_ALLOW_MULTISITE');
		$ret .= $this->wpDefine('WP_AUTO_UPDATE_CORE');
		$ret .= $this->wpDefine('AUTOMATIC_UPDATER_DISABLED');
		$ret .= $this->wpDefine('WP_AUTO_UPDATE_CORE');
		$ret .= $this->wpDefine('DISALLOW_FILE_EDIT');
		$ret .= $this->wpDefine('DISALLOW_FILE_MODS');

		return $ret;
	}

	private function wpDefine($define)
	{
		$ret = "$define: Off\n";
		if (defined($define)) {
			$ret = "$define: " . (constant($define) ? 'On' : 'Off') . "\n";
		}

		return $ret;
	}

	private function database()
	{
		global $wpdb;

		$ret = "\n*** Database ***\n";
		$ret .= "MySQL Version: " . $wpdb->db_version() . "\n";
		$ret .= "Database name: " . $wpdb->dbname . "\n";
		$ret .= "Host: " . $wpdb->dbhost . "\n";
		$ret .= "Table prefix: " . $wpdb->prefix . "\n";
		$ret .= "Charset: " . $wpdb->charset . "\n";


		return $ret;
	}

	private function webServer()
	{
		$maxExecution = ini_get('max_execution_time') ? ini_get('max_execution_time') : 30;
		$ret = "\n*** Server ***\n";
		$ret .= "Web server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
		$ret .= "PHP Version: " . PHP_VERSION . "\n";
		$ret .= "PHP Interface: " . php_sapi_name() . "\n";
		$ret .= "PHP Memory Limit: " . ini_get('memory_limit')."\n";
		$ret .= "PHP Max execution time:" . $maxExecution . "s \n";
		$ret .= "PHP Max Upload Size: " . ini_get( 'upload_max_filesize' ). "\n";
		$ret .= "PHP Post Max Size: " . ini_get( 'post_max_size' ) . "\n";

		$ret .= "fsockopen: " . (function_exists('fsockopen') ? 'Enabled':'Disabled') . "\n";

		return $ret;
	}

	private function installedPlugins()
	{
		$ret = "\n*** Plugins ***\n";
		$plugins = get_plugins();
		foreach ($plugins as $id => $plugin) {
			$ret .= sprintf('%s (%s) by %s %s',
				$plugin['Name'],
				$plugin['Version'],
				$plugin['Author'],
				is_plugin_active($id)? '' : '(inactive)'
			);
			$ret .= "\n";
		}

		return $ret;
	}

	private function phpExtensions()
	{
		$extensions = get_loaded_extensions();
		natcasesort($extensions);

		$ret = "\nPHP Extensions\n";

		foreach ($extensions as $extension) {
			$ret .= $extension . "\n";
		}

		return $ret;
	}

	private function installedThemes()
	{
		$ret = "\n*** Themes ***\n";

		$activeTheme = wp_get_theme();
		$themes = wp_get_themes();
		foreach($themes as $theme) {
			$ret .= sprintf('%s - %s' , $theme->name, $theme->version);
			if (strlen($theme->parent) > 0) {
				$ret .= ' CHILD_THEME';
			}
			if ($theme->name == $activeTheme->name) {
				$ret .= ' ACTIVE';
			}
			$ret .= "\n";
		}

		return $ret;
	}
}
