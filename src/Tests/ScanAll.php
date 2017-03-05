<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';

use integrityChecker\BackgroundProcess;
use integrityChecker\integrityChecker;
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
     * @var array
     */
    private $ignorePatterns;

    /**
     * @var string
     */
    private $tableName;

    /**
     * ScanAll constructor.
     *
     * @param null $session
     */
    public function __construct( $session ) {
        global $wpdb;

        parent::__construct( $session );

        $this->ignorePatterns = array(
            'wp-content/cache',
            'wp-config.php',
        );

        $plugin = integrityChecker::getInstance();
        $slug = str_replace('-', '_', $plugin->getPluginSlug());
        $this->tableName = $wpdb->prefix . $slug . '_files';
    }

    /**
     * @param $request
     */
    public function start($request)
    {
        $bgProcess = new BackgroundProcess();
        $this->session = $bgProcess->session;

        parent::start($request);
        $this->transientState = null;

        $this->clearTable(false);

        $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'scan'), 10);
        $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'shapeResult'), 20);
        $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'finish'), 99);

        $bgProcess->process(true);
    }

    /**
     * Main scan function. Will create jobs/tasks for each important sub folder
     *
     * @param $job
     */
    public function scan($job)
    {
        $bgProcess = new BackgroundProcess($this->session);
        $bgProcess->addJob((object)array(
            'class' => __CLASS__,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH, 'recursive' => false),
        ));

        $bgProcess->addJob((object)array(
            'class' => __CLASS__,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH . 'wp-admin', 'recursive' => true),
        ));

        $bgProcess->addJob((object)array(
            'class' => __CLASS__,
            'method' => 'scanFolder',
            'parameters' => array('folder' => ABSPATH . 'wp-includes', 'recursive' => true),
        ));


        // There's potentially 100s of subfolders under wp-content, too many
        // to safely scan in one go on weaker servers. We need to divide the work
        $wpContentSubfolders = $this->rglob(ABSPATH . 'wp-content/', '*', GLOB_ONLYDIR);
        $newJobs = array();
        foreach ($wpContentSubfolders as $folder) {
            $newJobs[] = (object)array(
                'class' => __CLASS__,
                'method' => 'scanFolder',
                'parameters' => array('folder' => $folder, 'recursive' => false),
            );
        }
        $bgProcess->addJobs($newJobs);
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
        $checkSummer->includeFolderInfo = true;
        $checkSummer->includeOwner = true;
        $checkSummer->recursive = $recursive;
        $files = $checkSummer->scan();

        $this->storeScanData($files);
    }

    /**
     * @param $job
     */
    public function shapeResult($job)
    {
        $files = $this->transientState;
        $this->transientState = array('result' => array(
            'ts' => time(),
            'files' => $files,
        ));

        // store new checkpoint?
        $storeNew = true;
        $plugin = integrityChecker::getInstance();
        $slug = $plugin->getPluginSlug();
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

    private function storeScanData($files)
    {
        global $wpdb;

        $columns = array('checkpoint', 'name', 'namehash', 'hash', 'modified', 'isdir', 'islink', 'size', 'mask',
            'fileowner', 'filegroup', 'mime', 'status');
        $sqlTemplate = "INSERT into {$this->tableName}(" . join(',', $columns) . ") " .
                       "VALUES(%d, %s, %s, %s, %d, %d, %d, %d, %d, %s, %s, %s, %s) ";

        foreach ($files->checksums as $name => $info) {

            $wpdb->insert(
                $this->tableName,
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

    private function clearTable($checkpoint = false)
    {
        global $wpdb;
        $wpdb->delete(
            $this->tableName,
            array('checkpoint' => $checkpoint ? '1' : 0)
        );
    }

    private function setCheckpoint()
    {
        global $wpdb;
        $fields = 'name, namehash, hash, modified, isdir, islink, size, mask, fileowner, filegroup, mime, status';
        $this->clearTable(true);
        $sql = "INSERT INTO {$this->tableName}(checkpoint, $fields)\n".
               "  SELECT 1, $fields\n" .
               "    FROM {$this->tableName} \n" .
               "    WHERE checkpoint = 0";

        $wpdb->query($sql);
    }
}