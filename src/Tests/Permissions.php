<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';

use integrityChecker\BackgroundProcess;
use integrityChecker\integrityChecker;
use integrityChecker\State;
use WPChecksum\FolderChecksum;

/**
 * Class Permissions
 * @package integrityChecker\Tests
 */
class Permissions extends BaseTest
{
	/**
	 * @var string
	 */
    public $name = 'permissions';

	/**
	 * @var array
	 */
	private $ignorePatterns;

    /**
     * What permissions are acceptable
     *
     * @var array
     */
    private $acceptablePermissions;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $tableName;

    /**
     * Issue type, to keep track of result of analyzed files
     */
    const UNACCEPTABLE_MASK = 1;

    /**
     * Issue type, to keep track of result of analyzed files
     */
    const UNACCEPTABME_OWNER = 2;

    /**
     * Issue type, to keep track of result of analyzed files
     */
    const UNACCEPTABME_GROUP = 2;



	/**
	 * Permissions constructor.
	 *
	 * @param null $session
	 */
	public function __construct($session) {
        global $wpdb;

		parent::__construct($session);

		$this->acceptablePermissions = array(
			'file' => array('0644','0640','0600'),
			'folder' => array('0755','0750','0700'),
		);

		$this->ignorePatterns = array(
			'wp-content/cache',
			'wp-config.php',
		);

        $plugin = integrityChecker::getInstance();
        $this->slug = str_replace('-', '_', $plugin->getPluginSlug());
        $this->tableName = $wpdb->prefix . $this->slug . '_files';
	}

	/**
     * Start the process
     *
	 * @param $request
	 */
	public function start($request)
    {
        $limit = 900;
        $payload = json_decode($request->get_body());
        if (isset($payload->source) && $payload->source == 'manual') {
            $limit = 90;
        }

        $bgProcess = $this->getBackgroundProcess(new ScanAll(null), $request, $limit);
        $this->session = $bgProcess->session;

        parent::start($request);
        $this->transientState = '';
        $offset = $bgProcess->lastQueuePriority();

	    $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'analyze'), $offset + 20);
	    $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'finish'), $offset + 99);

	    $bgProcess->process(true);
    }

    /**
     * Analyze all files
     *
     * @param $job
     */
    public function analyze($job)
    {
        global $wpdb;
        $files = array();
        $total = 0;
        $unAcceptableCount = 0;

        $acceptableOwnerGroup = $this->getOwnerGroup();
        $acceptableMasks = $this->getAcceptableMasks();

        // nr of files to fetch in one go
        $chunk = 2000;
        $offset = 0;
        $done = false;

        while (!$done) {
            $done = true; // Assume this is the last run
            $sql = "select * from {$this->tableName} WHERE checkpoint=0 LIMIT $offset, $chunk";
            $rows = $wpdb->get_results($sql);
            $updates = array();

            foreach ($rows as $row) {
                $done = false; // found at least one row, main loop isn't done yet
                $total++;
                $issue = $this->analyzeFile($row, $acceptableOwnerGroup, $acceptableMasks);

                if (!is_array($updates[$issue])) {
                    $updates[$issue] = array();
                }
                $updates[$issue][] = $row->id;

                if ($issue > 0) {
                    $unAcceptableCount++;
                }
            }

            foreach ($updates as $issue => $ids) {
                $joined = join(',', $ids);
                $query = "update {$this->tableName} SET permissionsresult={$issue} " .
                          "WHERE id in($joined)";
                $wpdb->query($query);
            }

            $offset += $chunk;
        }


	    $this->transientState = array('result' => array(
		    'total' => $total,
		    'acceptable' => $total - $unAcceptableCount,
		    'unacceptable' => $unAcceptableCount,
	    ));
    }

    /**
     * Analyze individual file
     *
     * @param object $row
     * @param object $acceptableOwnerGroup
     * @param object $acceptableMasks
     *
     * @return bool|object
     */
    private function analyzeFile($row, $acceptableOwnerGroup, $acceptableMasks)
    {

        $permissionsOk = in_array(
            '0' . $row->mask,
            $row->isdir ? $acceptableMasks->folderMask : $acceptableMasks->fileMask
        );
        $ownerOk = in_array($row->fileowner, $acceptableOwnerGroup->owner);
        $groupOk = in_array($row->filegroup, $acceptableOwnerGroup->group);

        $issue = 0;
        if (!$permissionsOk) {
            $issue = $issue | self::UNACCEPTABLE_MASK;
        }

        if (!$ownerOk) {
            $issue = $issue | self::UNACCEPTABME_OWNER;
        }

        if (!$groupOk) {
            $issue = $issue | self::UNACCEPTABME_GROUP;
        }

        return $issue;

    }

    /**
     * Add test data from the database
     *
     * @param object $result
     * @return object
     */
    public function getRestResults($result)
    {
        $result['files'] = array();
        return $result;

        return (object)array(
            'file'   => $row->name,
            'type'   => $row->isdir ? 'Folder' : 'File',
            'isDir'  => $row->isdir,
            'mode'   => '0' . $row->mask,
            'owner'  => $row->fileowner,
            'group'  => $row->filegroup,
            'date'   => date('Y-m-d H:i:s', $row->modified),
            'size'   => $row->size,
            'reason' => join(' ', $reason),
        );

        $reason = array();
        $reason[] = $permissionsOk ? null : __('Unacceptable permission mask.', 'integrity-checker');
        $reason[] = $ownerOk ? null : __('Unacceptable file owner.', 'integrity-checker');
        $reason[] = $groupOk ? null : __('Unacceptable file group.', 'integrity-checker');

        usort($files, function($a, $b) {
            $cntA = substr_count($a->file, DIRECTORY_SEPARATOR);
            $cntB = substr_count($b->file, DIRECTORY_SEPARATOR);

            $aa = (($a->isDir || $cntA > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $cntB > 0) ? '1':'0') . $b->file;

            return strcasecmp($aa, $bb);
        });

        return $result;
    }


    /**
     * Get the intended owner/group from options or figure out the
     * most probable owner and group for this installation using
     * heuristics
     *
     * @return object stdClass with a owner and group memeber
     */
    private function getOwnerGroup()
    {
        global $wpdb;

        $ret = (object)array(
            'owner' => get_option($this->slug . '_file_owner', false),
            'group' => get_option($this->slug . '_file_group', false),
        );

        // if we have both via options
        if ($ret->owner && $ret->group) {
            return $ret;
        }

        $files = array('index.php', 'wp-settings.php', 'wp-blog-header.php');
        if (!$ret->owner) {
            $sql = "SELECT fileowner FROM {$this->tableName} \n" .
                   "WHERE name in('" . join("','", $files) . "')\n" .
                   "GROUP BY fileowner ORDER BY count(fileowner) DESC LIMIT 0,1";
            $ret->owner = $wpdb->get_col($sql);
        }

        if (!$ret->group) {
            $sql = "SELECT filegroup FROM {$this->tableName} \n" .
                   "WHERE name in('" . join("','", $files) . "')\n" .
                   "GROUP BY filegroup ORDER BY count(filegroup) DESC LIMIT 0,1";
            $ret->group = $wpdb->get_col($sql);
        }

        return $ret;
    }

    /**
     * Find acceptable permission masks from the options
     * or if not set, the default WordPress permissions
     *
     * @return object
     */
    private function getAcceptableMasks()
    {
        global $wpdb;

        $ret = (object)array(
            'fileMask' => get_option($this->slug . '_file_masks', array('0644','0640','0600')),
            'folderMask' => get_option($this->slug . '_folder_masks', array('0755','0750','0700')),
        );

        return $ret;
    }

}