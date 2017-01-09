<?php
namespace WPChecksum;

class CoreChecker extends BaseChecker
{
    /**
     * CoreChecker constructor.
     *
     * @param object $apiClient
     * @param bool   $localCache
     */
	public function __construct($apiClient, $localCache = false) {
		$this->softIssues = array(
			'licens-*.txt' => 'ADDED,DELETED,MODIFIED',
			'*.htaccess' => 'ADDED',
            'readme.html' => 'DELETED',
		);

        parent::__construct($apiClient, $localCache);
	}

	public function check()
    {
	    global $wp_local_package;

        $ret = array();
        $ret['type'] = 'wordpress';
        $ret['slug'] = 'core';
        $ret['name'] = 'Core';
        $ret['version'] = get_bloginfo('version');
        $locale = $wp_local_package;
        if ($locale == 'en_US') {
            $locale = null;
        }

        $original = $this->getOriginal($ret['version'], $locale);
        if ($original) {
            $local = $this->getLocal();

            $changeSet = $this->getChangeSet($original, $local);
            $ret['status'] = 'checked';
            $ret['message'] = '';
            $ret['changeset'] = $changeSet;
        } else {
            $ret['status'] =  'unchecked';
            $ret['message'] = 'Original not found';
            $ret['changeset'] = array();
        }

        return $ret;
    }

    private function getOriginal($version, $locale)
    {
        $key = sprintf('wpchecksum_%s_%s_%s', 'core', $version, $locale);
        // cached in transient?
        if ($hit = get_transient($key)) {
            return $hit;
        }

        $out = new \stdClass();
        $originalCore = get_core_checksums($version, $locale);

        if ($originalCore) {

	        if (strlen($locale)) {
		        $arrChecksums = $originalCore;
	        } else {
		        $arrChecksums = $originalCore[$version];
	        }

            $checksums = array();
            foreach ($arrChecksums as $file => $hash) {
                if (stripos($file, 'wp-content') === 0) {
                    continue;
                }
                $obj = new \stdClass();
                $obj->hash = $hash;
                $checksums[$file] = $obj;
            }
            $out->status = 200;
            $out->checksums = $checksums;

            set_transient($key, $out, HOUR_IN_SECONDS * 24 *30);
            return $out;
        }
        return false;
    }

    private function getLocal()
    {
        $checkSummer = new FolderChecksum(ABSPATH);
        $checkSummer->recursive = false;
        $root = $checkSummer->scan();
        if (isset($root->checksums['wp-config.php'])) {
            unset($root->checksums['wp-config.php']);
        }

        $checkSummer = new FolderChecksum(ABSPATH . 'wp-admin', ABSPATH);
        $admin = $checkSummer->scan();


        $checkSummer = new FolderChecksum(ABSPATH . 'wp-includes', ABSPATH);
        $includes = $checkSummer->scan();


        $root->checksums = array_merge($root->checksums, $admin->checksums);
        $root->checksums = array_merge($root->checksums, $includes->checksums);

        return $root;

    }
}