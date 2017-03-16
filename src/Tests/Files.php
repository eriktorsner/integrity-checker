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
    const UNACCEPTABME_GROUP = 4;


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
            $limit = 10;
        }

        // This test depends on scanAll
        $scanAll = $this->testFactory->getTestObject('scanall');
        $scanAll->setBackgroundProcess($this->backgroundProcess);
        $this->initBackgroundProcess($scanAll, $request, $limit);

        $this->session = $this->backgroundProcess->session;

        parent::start($request);

        // Queue up our jobs
        $this->transientState = array('result' => array());
        $offset = $this->backgroundProcess->lastQueuePriority();
        $this->backgroundProcess->addJob((object)array(
            'class' => $this->name,
            'method' => 'analyzePermissions',
        ), $offset + 20);


        $this->backgroundProcess->addJob((object)array(
            'class' => $this->name,
            'method' => 'analyzeModifiedFiles',
        ), $offset + 20);

        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'finish'), $offset + 99);
    }

    /**
     * Analyze permission issues
     *
     * @param $job
     */
    public function analyzePermissions($job)
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
                $issue = $this->analyzePermissionForFile($row, $acceptableOwnerGroup);

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

	    $this->transientState['result']['permissions'] =array(
		    'total' => $total,
		    'acceptable' => $total - $unAcceptableCount,
		    'unacceptable' => $unAcceptableCount,
	    );

    }

    /**
     * Analyze new, changed or deleted files
     * At this point, just bother with the counts
     *
     * @param $job
     */
    public function analyzeModifiedFiles($job)
    {
        global $wpdb;
        $modified = $wpdb->get_col($this->getModifiedFilesSQL(true));
        $deleted = $wpdb->get_col($this->getDeletedFilesSQL(true));
        $added = $wpdb->get_col($this->getNewFilesSQL(true));

        $this->transientState['result']['modifiedfiles'] = array(
            'MODIFIED' => $modified[0],
            'DELETED' => $deleted[0],
            'ADDED' => $added[0],
        );

    }

    /**
     * Add test data from the database
     *
     * @param object $result
     * @return object
     */
    public function getRestResults($result)
    {
        $result = $this->getPermissionsRestResult($result);
        $result = $this->getModifiedFilesRestResult($result);

        return $result;
    }

    /**
     * Add Permissions test data from the database
     *
     * @param object $result
     * @return object
     */
    private function getPermissionsRestResult($result)
    {
        global $wpdb;
        $tableName = $this->getTableName();

        $sql = "select * from $tableName WHERE checkpoint=0 AND permissionsresult != 0";
        $rows = $wpdb->get_results($sql);
        $files = array();

        foreach ($rows as $row) {
            $reasons = array();
            if ($row->permissionsresult & self::UNACCEPTABME_OWNER) {
                $reasons[] = __("Owner", 'integrity-checker');
            }
            if ($row->permissionsresult & self::UNACCEPTABME_GROUP) {
                $reasons[] = __("Group", 'integrity-checker');
            }
            if ($row->permissionsresult & self::UNACCEPTABLE_MASK) {
                $reasons[] = __("Mask", 'integrity-checker');
            }
            $files[] = (object)array(
                'file'   => $row->name,
                'type'   => $row->isdir ? 'Folder' : 'File',
                'isDir'  => $row->isdir,
                'mode'   => '0' . $row->mask,
                'owner'  => $row->fileowner,
                'group'  => $row->filegroup,
                'date'   => date('Y-m-d H:i:s', $row->modified),
                'size'   => $row->size,
                'issue' => join(' + ', $reasons),
            );
        }

        usort($files, function($a, $b) {
            $cntA = substr_count($a->file, DIRECTORY_SEPARATOR);
            $cntB = substr_count($b->file, DIRECTORY_SEPARATOR);

            $aa = (($a->isDir || $cntA > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $cntB > 0) ? '1':'0') . $b->file;

            return strcasecmp($aa, $bb);
        });

        $result['permissions']['files'] = $files;

        return $result;
    }

    /**
     * Add Modified files test data from the database
     *
     * @param $result
     *
     * @return mixed
     */
    private function getModifiedFilesRestResult($result)
    {
        global $wpdb;
        $tableName = $this->getTableName();

        $files = array();
        foreach (array('MODIFIED', 'DELETED', 'ADDED') as $changeType) {

            $result['modifiedfiles'][$changeType] = isset($result['modifiedfiles'][$changeType]) ?
                (int)$result['modifiedfiles'][$changeType] :
                0;

            switch ($changeType) {
                case 'MODIFIED':
                    $rows = $wpdb->get_results($this->getModifiedFilesSQL(false));
                    break;
                case 'DELETED':
                    $rows = $wpdb->get_results($this->getDeletedFilesSQL(false));
                    break;
                case 'ADDED':
                    $wpdb->get_results($this->getNewFilesSQL(false));
                    break;
            }

            foreach ($rows as $row) {

                $files[] = (object)array(
                    'file'   => $row->name,
                    'type'   => $row->isdir ? 'Folder' : 'File',
                    'isDir'  => $row->isdir,
                    'mode'   => '0' . $row->mask,
                    'owner'  => $row->fileowner,
                    'group'  => $row->filegroup,
                    'date'   => date('Y-m-d H:i:s', $row->modified),
                    'size'   => $row->size,
                    'issue' => $changeType,
                );
            }
        }

        usort($files, function($a, $b) {
            $cntA = substr_count($a->file, DIRECTORY_SEPARATOR);
            $cntB = substr_count($b->file, DIRECTORY_SEPARATOR);

            $aa = (($a->isDir || $cntA > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $cntB > 0) ? '1':'0') . $b->file;

            return strcasecmp($aa, $bb);
        });

        $result['modifiedfiles']['files'] = $files;

        return $result;
    }

    /**
     * Analyze individual file
     *
     * @param object $row
     * @param object $acceptableOwnerGroup
     *
     * @return bool|object
     */
    private function analyzePermissionForFile($row, $acceptableOwnerGroup)
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
     * Return a SQL query to get all modified files. Returns
     * data about the files or just a count.
     *
     * @param bool $count
     *
     * @return string
     */
    private function getModifiedFilesSQL($count = false)
    {
        $fields = $count ?
            "count(*)" :
            "cu.id, cu.name, cu.modified, cu.mask, cu.fileowner, cu.filegroup, cu.isdir, cu.size";

        $tableName = $this->getTableName();
        $sql = "SELECT $fields \n" .
                "FROM {$tableName} cp\n" .
                "LEFT OUTER JOIN {$tableName} cu\n" .
                "  ON (cu.namehash = cp.namehash AND cu.checkpoint=0 AND cp.checkpoint=1)\n" .
                "WHERE cu.hash != cp.hash;";

        return $sql;
    }

    /**
     * Return a SQL query to get all deleted files. Returns
     * data about the files or just a count.
     *
     * @param bool $count
     *
     * @return string
     */
    private function getDeletedFilesSQL($count = false)
    {
        $fields = $count ?
            "count(*)" :
            "cp.id, cp.name, cp.modified, cp.mask, cp.fileowner, cp.filegroup, cp.isdir, cp.size";

        $tableName = $this->getTableName();

        $sql = "SELECT $fields\n" .
            "FROM $tableName cp\n" .
            "WHERE cp.checkpoint=1\n" .
            "AND cp.namehash not in(\n" .
            "  select namehash from wordpress.wp_integrity_checker_files WHERE checkpoint = 0);";

        return $sql;
    }

    /**
     * Return a SQL query to get all new files. Returns
     * data about the files or just a count.
     *
     * @param bool $count
     *
     * @return string
     */
    private function getNewFilesSQL($count = false)
    {
        $fields = $count ?
            "count(*)" :
            "curr.id, curr.name, curr.modified, curr.isdir, curr.islink, curr.size";

        $tableName = $this->getTableName();

        $sql = "SELECT $fields\n" .
               "FROM $tableName curr\n" .
               "WHERE curr.checkpoint=0\n" .
               "AND curr.namehash not in(\n" .
               "  select namehash from wordpress.wp_integrity_checker_files WHERE checkpoint = 1);";

        return $sql;
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