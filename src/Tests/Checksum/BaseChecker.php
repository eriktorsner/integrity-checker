<?php
namespace WPChecksum;

class BaseChecker
{
    const PLUGIN_URL_TEMPLATE = "https://downloads.wordpress.org/plugin/%s.%s.zip";
    const THEME_URL_TEMPLATE  = "https://downloads.wordpress.org/theme/%s.%s.zip";

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * @var array
     */
    protected $softIssues = array();

    /**
     * Client for requests to api.wpessentials.io
     * @var object
     */
    protected $apiClient;

    /**
     * BaseChecker constructor.
     *
     * @param object $apiClient
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Read local checksums
     *
     * @param $path
     * @return \stdClass
     */
    public function getLocalChecksums($path)
    {
        $checkSummer = new FolderChecksum($path);
        // Symlinks inside plugins and themes make no sense
        $checkSummer->followSymlinks = false;
        $out = $checkSummer->scan();

        return $out;
    }

    /**
     * Read original checksums from the API
     *
     * @param string $type
     * @param string $slug
     * @param string $version
     *
     * @return array|mixed|null|object|\stdClass|\WP_Error
     */
    public function getOriginalChecksums($type, $slug, $version)
    {
        $key = sprintf('wpchecksum_%s_%s_%s', $type, $slug, $version);
        $hit = get_transient($key);

        if ($hit !== false) {
            return $hit;
        }

        $out = $this->apiClient->getChecksums($type, $slug, $version);

        set_transient($key, $out, HOUR_IN_SECONDS);
        return $out;
    }

    /**
     * Compare the original set of files/checksums to the local
     * set.
     *
     * @param array $original
     * @param array $local
     *
     * @return array A set of changed files
     */
    public function getChangeSet($original, $local)
    {
        $changeSet = array();
        foreach ($original->checksums as $key => $originalFile) {
            if (isset($local->checksums[$key])) {
                if ($originalFile->hash != $local->checksums[$key]->hash) {
                    $altMatch = false;
                    if (isset($originalFile->alt)) {
                        foreach ($originalFile->alt as $altChecksum) {
                            if ($altChecksum->hash == $local->checksums[$key]->hash) {
                                $altMatch = true;
                            }
                        }
                    }

                    if (!$altMatch) {
                        $change          = $originalFile;
                        $change->status  = 'MODIFIED';
                        $change->date    = $local->checksums[$key]->date;
                        $change->size    = $local->checksums[$key]->size;
                        $change->isSoft  = $this->isSoftChange($key, $change->status);
                        $changeSet[$key] = $change;
                    }
                }
            } else {
                $change          = $originalFile;
                $change->status  = 'DELETED';
                $change->isSoft  = $this->isSoftChange($key, $change->status);
                $changeSet[$key] = $change;
            }
        }

        foreach ($local->checksums as $key => $localFile) {
            if (!isset($original->checksums[$key])) {
                $change          = $localFile;
                $change->status  = 'ADDED';
                $change->isSoft  = $this->isSoftChange($key, $change->status);
                $changeSet[$key] = $change;
            }
        }

        return $changeSet;
    }

    /**
     * Check if a change can be classified as "soft"
     *
     * @param string $changedFile
     * @param string $status
     *
     * @return boolean
     */
    private function isSoftChange($changedFile, $status)
    {
	    foreach ($this->softIssues as $pattern => $allowed) {
		    if (fnmatch($pattern, $changedFile)) {
			    return true;
		    }
	    }

	    return false;
    }

}