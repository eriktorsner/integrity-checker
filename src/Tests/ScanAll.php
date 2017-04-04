<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';

use integrityChecker\BackgroundProcess;
use integrityChecker\integrityChecker;
use integrityChecker\Settings;
use WPChecksum\FolderChecksum;

/**
 * Class Permissions
 * @package integrityChecker\Tests
 */
class ScanAll extends BaseTest
{
    /**
     * @var string
     */
    public $name = 'scanall';


    /**
     * @param $request
     */
    public function start($request)
    {
        global $wpdb;

        $this->backgroundProcess->init();

        parent::start($request);

        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'scan'), 10);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'analyze'), 20);
        $this->backgroundProcess->addJob((object)array('class' => $this->name, 'method' => 'finish'), 99);

    }

    /**
     * Main scan function. Will create jobs for each included sub folder
     * with prio = 10
     *
     * @param $job
     */
    public function scan($job)
    {
        global $wpdb;

        $scanStart = time();
        $this->transientState = array('scanStart' => $scanStart);

        // Assume all files are deleted until we detect them again
        $this->markLatestFilesDeleted($scanStart);

        // There's potentially 100s of subfolders under the wp root, too many
        // to safely scan in one go on weaker servers. We need to divide the work
        $subFolders = $this->rglob(ABSPATH, '*', GLOB_ONLYDIR, $this->settings->followSymlinks);


        $newJobs = array();
        foreach ($subFolders as $folder) {

            $newJobs[] = (object)array(
                'class' => $this->name,
                'method' => 'scanFolder',
                'parameters' => array('folder' => $folder, 'recursive' => false),
            );

        }

        $newJobs[] = (object)array(
            'class' => $this->name,
            'method' => 'scanFolder',
            'parameters' => array('folder' => rtrim(ABSPATH, '/'), 'recursive' => false),
        );

        $this->backgroundProcess->addJobs($newJobs, 10);
    }

    /**
     * @param int $scanStart
     */
    private function markLatestFilesDeleted($scanStart)
    {
        global $wpdb;

        $tableName = $this->getTableName();
        $sql = "UPDATE $tableName f " .
               "  LEFT OUTER JOIN $tableName l ON f.name = l.name AND f.version < l.version " .
               "SET f.deleted=%d " .
               "WHERE l.id IS NULL AND f.deleted IS NULL;";
        $wpdb->query($wpdb->prepare($sql, array($scanStart)));
    }

    /**
     * Scan an individual folder
     *
     * @param $job
     */
    public function scanFolder($job)
    {
        require_once ABSPATH.'/wp-admin/includes/file.php';

        $folder = $job->parameters['folder'];
        $recursive = $job->parameters['recursive'];

        $checkSummer = new FolderChecksum($folder, ABSPATH);
        $checkSummer->calcHash = true;
        $checkSummer->maxFileSize = $this->settings->maxFileSize * 1024 * 1024;
        $checkSummer->includeFolderInfo = true;
        $checkSummer->includeOwner = true;
        $checkSummer->recursive = $recursive;
        $checkSummer->followSymlinks = $this->settings->followSymlinks;
        $files = $checkSummer->scan();

        $this->storeScanData($files);
    }

    /**
     * @param $job
     */
    public function analyze($job)
    {
        $this->transientState = array('result' => array(
            'ts' => time(),
        ));
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function truncateHistory($data)
    {
        global $wpdb;
        $tableName = $this->getTableName();

        if (!is_object($data) || !isset($data->deleteScanHistoryRange)) {
            return new \WP_Error('fail', 'Missing parameter', array('status' => 400));
        }


        if ($data->deleteScanHistoryRange == 'all') {
            $time = time();
        } else {
            $time = strtotime($data->deleteScanHistoryRange, time());
        }

        if ($time) {
            // 1. delete
            $sql = "DELETE f FROM $tableName f\n" .
                    " LEFT OUTER JOIN $tableName l ON f.name = l.name AND f.version < l.version\n".
                    "WHERE (l.id IS NOT NULL AND f.found < %d) OR (l.id IS NULL AND f.deleted < %d);";
            $wpdb->query($wpdb->prepare($sql, array($time, $time)));

            // 2. get the current firstScan
            //$firstScan = $wpdb->get_var("select min(found) from $tableName");

            // 2. update version and founddate on the first of each
            $sql = "UPDATE $tableName f\n".
                    "LEFT OUTER JOIN $tableName l ON f.name = l.name AND f.version > l.version\n".
                    "SET f.found=%d, f.version=1\n".
                    "WHERE l.id IS NULL";
            $wpdb->query($wpdb->prepare($sql, array($time)));
        }

    }

    /**
     * Store the scan result in files table
     *
     * @param $files
     */
    private function storeScanData($files)
    {
        global $wpdb;

        $scanStart = $this->transientState['scanStart'];
        $tableName = $this->getTableName();
        $foundIds = array();

        foreach ($files->checksums as $name => $info) {

            $existing = $wpdb->get_row($wpdb->prepare(
               "select id, name, version, hash, mode, fileowner, filegroup FROM $tableName WHERE name=%s " .
               "ORDER BY version desc LIMIT 0,1",
                array($name)
            ));

            $changed = true;
            if ($existing) {
                $foundIds[] = $existing->id;
                $changed = $existing->hash != $info->hash
                            || $existing->fileowner != $info->owner
                            || $existing->filegroup != $info->group
                            || $existing->mode != (int)$info->mode;
            }

            if (!$existing || $changed) {
                $version = $existing ? $existing->version + 1 : 1;
                $wpdb->insert(
                    $tableName,
                    array(
                        'version'    => $version,
                        'name'       => $name,
                        'hash'       => $info->hash,
                        'found'      => $scanStart,
                        'modified'   => $info->date,
                        'isdir'      => $info->isDir,
                        'islink'     => $info->isLink,
                        'size'       => $info->size,
                        'mode'       => $info->mode,
                        'fileowner'  => $info->owner,
                        'filegroup'  => $info->group,
                    ),
                    array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
                );
            }
        }

        // Reset the deleted flag
        if (count($foundIds) > 0) {
            $wpdb->query(sprintf(
                "UPDATE $tableName SET deleted = NULL WHERE id in(%s) AND deleted=%d",
                join(',', $foundIds),
                $scanStart
            ));
        }
    }
}