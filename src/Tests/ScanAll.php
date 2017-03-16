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
        $this->transientState = null;

        $this->clearTable(false);

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
        $this->backgroundProcess->addJob((object)array(
            'class' => $this->name,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH, 'recursive' => false),
        ));

        $this->backgroundProcess->addJob((object)array(
            'class' => $this->name,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH . 'wp-admin', 'recursive' => true),
        ));

        $this->backgroundProcess->addJob((object)array(
            'class' => $this->name,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH . 'wp-includes', 'recursive' => true),
        ));


        // There's potentially 100s of subfolders under wp-content, too many
        // to safely scan in one go on weaker servers. We need to divide the work
        $wpContentSubfolders = $this->rglob(ABSPATH . 'wp-content/', '*', GLOB_ONLYDIR);
        $newJobs = array();
        foreach ($wpContentSubfolders as $folder) {
            $newJobs[] = (object)array(
                'class' => $this->name,
                'method' => 'scanFolder',
                'parameters' => array('folder' => $folder, 'recursive' => false),
            );
        }
        $this->backgroundProcess->addJobs($newJobs, 10);
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

        // store new checkpoint?
        $storeNew = true;
        $slug = $this->settings->slug;
        $existingCheckpoint = get_option("{$slug}_files_checkpoint", false);
        if ($existingCheckpoint) {
            $maxCheckpointAge = 604800; // TODO: need a setting for this
            if (time() < ($existingCheckpoint->ts + $maxCheckpointAge)) {
                $storeNew = false;
            }
        }

        if ($storeNew) {
            $this->setCheckpoint();
            update_option(
                "{$slug}_files_checkpoint",
                (object)array('ts' => time(),)
            );
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

        $tableName = $this->getTableName();

        foreach ($files->checksums as $name => $info) {

            $wpdb->insert(
                $tableName,
                array(
                    'checkpoint' => 0,
                    'name'       => $name,
                    'namehash'   => md5($name),
                    'hash'       => $info->hash,
                    'modified'   => $info->date,
                    'isdir'      => $info->isDir,
                    'islink'     => $info->isLink,
                    'size'       => $info->size,
                    'mask'       => $info->mode,
                    'fileowner'  => $info->owner,
                    'filegroup'  => $info->group,
                ),
                array('%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Delete rows from the files table
     *
     * @param bool $checkpoint
     */
    private function clearTable($checkpoint = false)
    {
        global $wpdb;
        $wpdb->delete(
            $this->getTableName(),
            array('checkpoint' => $checkpoint ? '1' : 0)
        );
    }

    /**
     * Create a checkpoint from the current scan result
     */
    private function setCheckpoint()
    {
        global $wpdb;
        $tableName = $this->getTableName();
        $fields = 'name, namehash, hash, modified, isdir, islink, size, mask, fileowner, filegroup, mime, status';
        $this->clearTable(true);
        $sql = "INSERT INTO $tableName(checkpoint, $fields)\n".
               "  SELECT 1, $fields\n" .
               "    FROM $tableName \n" .
               "    WHERE checkpoint = 0";

        $wpdb->query($sql);
    }

}