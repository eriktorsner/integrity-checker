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
            $limit = 0;
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
            $sql = "select f.* FROM wp_integrity_checker_files f " .
                "LEFT OUTER JOIN wp_integrity_checker_files l ON f.name = l.name AND f.version < l.version " .
                "WHERE l.id IS NULL " .
                "LIMIT $offset, $chunk";
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

        $tableName = $this->getTableName();
        $firstScan = $wpdb->get_var("select min(found) from $tableName");

        $modified = $wpdb->get_col($wpdb->prepare($this->getModifiedFilesSQL(true), array($firstScan)));
        $deleted = $wpdb->get_col($wpdb->prepare($this->getDeletedFilesSQL(true), array($firstScan)));
        $added = $wpdb->get_col($wpdb->prepare($this->getNewFilesSQL(true), array($firstScan)));

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

        $sql = "select f.*, l.id \n" .
            "FROM wp_integrity_checker_files f \n" .
            "  LEFT OUTER JOIN wp_integrity_checker_files l ON f.name = l.name AND f.version < l.version \n" .
            "WHERE l.id IS NULL AND l.deleted IS NULL;";

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
                'mode'   => '0' . $row->mode,
                'owner'  => $row->fileowner,
                'group'  => $row->filegroup,
                'date'   => date('Y-m-d H:i:s', $row->modified),
                'size'   => $row->size,
                'issue' => join(' + ', $reasons),
                'level' => substr_count($row->name, DIRECTORY_SEPARATOR)
            );
        }

        usort($files, function($a, $b) {
            $aa = (($a->isDir || $a->level > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $b->level > 0) ? '1':'0') . $b->file;

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
        $firstScan = $wpdb->get_var("select min(found) from $tableName");


        $files = array();
        foreach (array('MODIFIED', 'DELETED', 'ADDED') as $changeType) {

            switch ($changeType) {
                case 'MODIFIED':
                    $rows = $wpdb->get_results($wpdb->prepare(
                        $this->getModifiedFilesSQL(false),
                        array($firstScan)
                    ));
                    break;
                case 'DELETED':
                    $rows = $wpdb->get_results($wpdb->prepare(
                        $this->getDeletedFilesSQL(false),
                        array($firstScan)
                    ));
                    break;
                case 'ADDED':
                    $rows = $wpdb->get_results($wpdb->prepare(
                        $this->getNewFilesSQL(false),
                        array($firstScan)
                    ));
                    break;
            }
            $result['modifiedfiles'][$changeType] = count($rows);

            foreach ($rows as $row) {

                $files[] = (object)array(
                    'file'   => $row->name,
                    'type'   => $row->isdir ? 'Folder' : 'File',
                    'isDir'  => $row->isdir,
                    'mode'   => '0' . $row->mode,
                    'owner'  => $row->fileowner,
                    'group'  => $row->filegroup,
                    'date'   => date('Y-m-d H:i:s', $row->modified),
                    'size'   => $row->size,
                    'issue' => $changeType,
                    'level' => substr_count($row->name, DIRECTORY_SEPARATOR),
                );
            }
        }

        usort($files, function($a, $b) {
            $aa = (($a->isDir || $a->level > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $b->level > 0) ? '1':'0') . $b->file;

            return strcasecmp($aa, $bb);
        });

        $result['modifiedfiles']['checkpoint'] = $firstScan;
        $result['modifiedfiles']['checkpointIso'] = date('Y-m-d H:i:s', $firstScan);
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
            '0' . $row->mode,
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
            "f.id, f.name, f.modified, f.mode, f.fileowner, f.filegroup, f.isdir, f.size";

        $tableName = $this->getTableName();
        $sql = "SELECT $fields \n" .
                "FROM {$tableName} f\n" .
                "LEFT OUTER JOIN {$tableName} l\n" .
                "  ON (f.name = l.name AND f.version < l.version)\n" .
                "WHERE l.id IS NULL AND f.deleted IS NULL AND f.version > 1 AND f.found > %d;";

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
            "f.id, f.name, f.modified, f.mode, f.fileowner, f.filegroup, f.isdir, f.size";

        $tableName = $this->getTableName();

        $sql = "SELECT $fields \n" .
               "FROM {$tableName} f\n" .
               "LEFT OUTER JOIN {$tableName} l\n" .
               "  ON (f.name = l.name AND f.version < l.version)\n" .
               "WHERE l.id IS NULL AND f.deleted IS NOT NULL;";

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
            "f.id, f.name, f.modified, f.mode, f.fileowner, f.filegroup, f.isdir, f.size";

        $tableName = $this->getTableName();

        $sql = "SELECT $fields \n" .
               "FROM {$tableName} f\n" .
               "LEFT OUTER JOIN {$tableName} l\n" .
               "  ON (f.name = l.name AND f.version < l.version)\n" .
               "WHERE l.id IS NULL AND f.deleted IS NULL AND f.version = 1 AND f.found > %d;";

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