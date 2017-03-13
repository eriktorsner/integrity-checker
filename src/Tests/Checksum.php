<?php
namespace integrityChecker\Tests;

// Manually include files from different namespace.
require_once __DIR__ . '/Checksum/BaseChecker.php';
require_once __DIR__ . '/Checksum/FolderChecksum.php';
require_once __DIR__ . '/Checksum/CoreChecker.php';
require_once __DIR__ . '/Checksum/PluginChecker.php';
require_once __DIR__ . '/Checksum/ThemeChecker.php';

use integrityChecker\BackgroundProcess;
use integrityChecker\ApiClient;
use WPChecksum\CoreChecker;
use \WPChecksum\ThemeChecker;
use \WPChecksum\PluginChecker;

class Checksum extends BaseTest
{
	/**
	 * @var BackgroundProcess
	 */
    public $name = 'checksum';

    public function start($request)
    {
        $this->backgroundProcess->init();

        parent::start($request);

        $this->transientState = array();
        $offset = $this->backgroundProcess->lastQueuePriority();

	    // Get the jobs into the queue
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkCore'), $offset +1);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkPlugins'), $offset +1);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'checkThemes'), $offset +1);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'analyze'), $offset +20);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'finish'), $offset +99);

        $this->backgroundProcess->process();

    }

    /**
     * Iterate through all fetched data and store the results
     *
     * @param object $job
     */
    public function analyze($job)
    {
        $out = array(
            'core'    => array(),
            'plugins' => array(),
            'themes'  => array(),
        );

        foreach ($this->transientState as $type => $items) {
            foreach ($items as $item) {
                $obj = new \stdClass();
                $obj->name = $item['name'];
                $obj->slug = $item['slug'];
                $obj->version = $item['version'];
                $obj->status = $item['status'];
                $obj->message = $item['message'];
                if (isset($item['error'])) {
                    switch ($item['error']) {
                        case ApiClient::NO_APIKEY:
                            $obj->message = __('No API key found or created', 'integrity-checker');
                            break;
                        case ApiClient::INVALID_APIKEY:
                            $obj->message = __('Invalid API key', 'integrity-checker');
                            break;
                        case ApiClient::RATE_LIMIT_EXCEEDED:
                            $obj->message = __('API rate limit exceeded', 'integrity-checker');
                            break;
                        case ApiClient::RESOURCE_NOT_FOUND:
                            $obj->message = __(
                                'Plugin/theme or it\'s version not found. Original checksums not fetched',
                                'integrity-checker'
                            );
                            break;
                    }
                }
                $obj->issues = array();

	            $issueCounts = $this->getIssueCounts($item['changeset']);
	            $obj->totalIssues = $issueCounts['totalIssues'];
	            $obj->hardIssues = $issueCounts['hardIssues'];
	            $obj->softIssues = $issueCounts['softIssues'];

                foreach ($item['changeset'] as $fileName => $issue) {
                    $issue->file = $fileName;
                    if (isset($issue->date) && (int)$issue->date > 0) {
                        $issue->date = date('Y-m-d H:i:s', (int)$issue->date);
                    } else {
                        $issue->date = '';
                    }
                    $issue->size = isset($issue->size)?(int)$issue->size:0;
                    $obj->issues[] = $issue;
                }
                $out[$type][$obj->slug] = $obj;
            }
        }

	    $this->transientState = array( 'result' => $out);
    }

	/**
	 * Check WP core
	 *
	 * @param object $job
	 */
    public function checkCore($job)
    {
        require_once ABSPATH.'/wp-admin/includes/update.php';
        require_once ABSPATH.'/wp-admin/includes/file.php';
        $out = array();
        $checker = new CoreChecker(false);
        $out[] = $checker->check();

	    $this->transientState['core'] = $out;
    }

	/**
	 * Check all plugins
	 *
	 * @param object $job
	 */
	public function checkPlugins($job)
    {
        // ensure the plugins file is loaded from core
        require_once ABSPATH.'/wp-admin/includes/plugin.php';

        $plugins = get_plugins();
	    $this->transientState['plugins'] = array();

        $ignoredPlugins = isset($this->settings->checksumIgnore['plugins']) ?
            $this->settings->checksumIgnore['plugins'] :
            array();

        foreach ($plugins as $id => $plugin) {
            // Ignore this plugin?
            if (in_array($id, $ignoredPlugins)) {
                continue;
            }
	        $this->backgroundProcess->addJob((object)array(
				'class' => $this->name,
		        'method' => 'checkPlugin',
		        'parameters' => array('id' => $id, 'plugin' => $plugin),
	        ));
        }

	    $this->transientState['plugins'] = array();
    }

	/**
	 * Check single plugin
	 *
	 * @param object $job
	 */
    public function checkPlugin($job)
    {
	    require_once ABSPATH.'/wp-admin/includes/file.php';

	    $id = $job->parameters['id'];
	    $plugin = $job->parameters['plugin'];

	    $checker = new PluginChecker($this->apiClient, false);
	    $this->transientState['plugins'][] = $checker->check($id, $plugin);
    }

	/**
	 * Check all themes
	 *
	 * @param $job
	 */
	public function checkThemes($job)
    {
        require_once ABSPATH.'/wp-admin/includes/file.php';
        $themes = wp_get_themes();
        $out = array();
        foreach ($themes as $slug => $theme) {
            // Don't attempt to check child themes
            if ($theme['template'] != $slug) {
                continue;
            }
            $checker = new ThemeChecker($this->apiClient, false);
            $out[] = $checker->check($slug, $theme);
        }

	    $this->transientState['themes'] = $out;
    }

	/**
	 * Calculate nr of issues by type
	 *
	 * @param array $changeSet
	 *
	 * @return array
	 */
    private function getIssueCounts($changeSet)
    {
		$ret = array(
			'totalIssues' => 0,
			'softIssues' => 0,
			'hardIssues' => 0,
		);

	    foreach ($changeSet as $file) {
		    $ret['totalIssues']++;
		    if (isset($file->isSoft) && $file->isSoft) {
			    $ret['softIssues']++;
		    }
	    }

	    $ret['hardIssues'] = $ret['totalIssues'] - $ret['softIssues'];

	    return $ret;

    }
}