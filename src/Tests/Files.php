<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';


/**
 * Class Permissions
 * @package integrityChecker\Tests
 */
class Files extends BaseTest
{
	/**
	 * @var string
	 */
    public $name = 'files';

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
     * Start the process
     *
	 * @param $request
	 */
	public function start($request)
    {
        global $wpdb;

        $limit = 900;
        $payload = json_decode($request->get_body());
        if (isset($payload->source) && $payload->source == 'manual') {
            $limit = 90;
        }

        // This test depends on scanAll
        $scanAll = $this->testFactory->getTestObject('scanall');
        $scanAll->setBackgroundProcess($this->backgroundProcess);
        $this->initBackgroundProcess($scanAll, $request, $limit);
        $this->session = $this->backgroundProcess->session;

        parent::start($request);

        // Queue up our jobs
        $this->transientState = '';
        $offset = $this->backgroundProcess->lastQueuePriority();
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'analyze'), $offset + 20);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'finish'), $offset + 99);
        $this->backgroundProcess->process(true);
    }

    /**
     * Analyze all files
     *
     * @param $job
     */
    public function analyze($job)
    {
        global $wpdb;
        $total = 0;
        $unAcceptableCount = 0;
        $tableName = $this->getTableName();

        $acceptableOwnerGroup = $this->getOwnerGroup();

        // nr of files to fetch in one go
        $chunk = 2000;
        $offset = 0;
        $done = false;

        while (!$done) {
            $done = true; // Assume this is the last run
            $sql = "select * from $tableName WHERE checkpoint=0 LIMIT $offset, $chunk";
            $rows = $wpdb->get_results($sql);
            $updates = array();

            foreach ($rows as $row) {
                $done = false; // found at least one row, main loop isn't done yet
                $total++;
                $issue = $this->analyzeFile($row, $acceptableOwnerGroup);

                if (!isset($updates[$issue]) || !is_array($updates[$issue])) {
                    $updates[$issue] = array();
                }
                $updates[$issue][] = $row->id;

                if ($issue > 0) {
                    $unAcceptableCount++;
                }
            }

            // Update in chunks for better performane
            foreach ($updates as $issue => $ids) {
                $joined = join(',', $ids);
                $query = "update $tableName SET permissionsresult={$issue} " .
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
     *
     * @return bool|object
     */
    private function analyzeFile($row, $acceptableOwnerGroup)
    {
        $permissionsOk = in_array(
            '0' . $row->mask,
            $row->isdir ?
                explode(',', $this->settings->folderMasks) :
                explode(',', $this->settings->fileMasks)
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

        $tableName = $this->getTableName();

        $ret = (object)array(
            'owner' => strlen(trim($this->settings->fileOwners)) > 0 ?
                explode(',', $this->settings->fileOwners) :
                false,
            'group' => strlen(trim($this->settings->fileGroups)) > 0 ?
                explode(',', $this->settings->fileGroups) :
                false,
        );

        // if we have both via options
        if ($ret->owner && $ret->group) {
            return $ret;
        }

        $files = array('index.php', 'wp-settings.php', 'wp-blog-header.php');
        if (!$ret->owner) {
            $sql = "SELECT fileowner FROM $tableName \n" .
                   "WHERE name in('" . join("','", $files) . "')\n" .
                   "GROUP BY fileowner ORDER BY count(fileowner) DESC LIMIT 0,1";
            $ret->owner = $wpdb->get_col($sql);
        }

        if (!$ret->group) {
            $sql = "SELECT filegroup FROM $tableName \n" .
                   "WHERE name in('" . join("','", $files) . "')\n" .
                   "GROUP BY filegroup ORDER BY count(filegroup) DESC LIMIT 0,1";
            $ret->group = $wpdb->get_col($sql);
        }

        return $ret;
    }
}