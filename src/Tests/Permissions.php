<?php
namespace integrityChecker\Tests;

require_once __DIR__ . '/Checksum/FolderChecksum.php';

use integrityChecker\BackgroundProcess;
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
	 * Permissions constructor.
	 *
	 * @param null $session
	 */
	public function __construct( $session ) {
		parent::__construct( $session );

		$this->acceptablePermissions = array(
			'file' => array('0644','0640','0600'),
			'folder' => array('0755','0750','0700'),
		);

		$this->ignorePatterns = array(
			'wp-content/cache',
			'wp-config.php',
		);
	}

	/**
	 * @param $request
	 */
	public function start($request)
    {
	    $bgProcess = new BackgroundProcess();
	    $this->session = $bgProcess->session;

        parent::start($request);

	    $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'scan'));
	    $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'shapeResult'), 20);
	    $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'finish'), 99);

	    $bgProcess->process();
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
		$checkSummer->calcHash = false;
		$checkSummer->includeFolderInfo = true;
		$checkSummer->recursive = $recursive;
		$content = $checkSummer->scan();

		$this->transientState = array_merge($this->transientState, $content->checksums);
	}


    public function shapeResult($job)
    {
        $files = array();
        $total = 0;
        $acceptableCount = 0;

        foreach ($this->transientState as $fileName => $file) {
            $total++;
            $file->file = $fileName;
            $acceptable = in_array(
                $file->mode,
                $this->acceptablePermissions[$file->isDir ? 'folder' : 'file']
            );

            if (isset($file->date)) {
                $file->date = date('Y-m-d H:i:s', $file->date);
            } else {
                $file->date = '';
            }

            if (!$acceptable) {
                $files[] = $file;
            } else {
	            $acceptableCount++;
            }
        }

	    usort($files, function($a, $b) {
		    $cntA = substr_count($a->file, DIRECTORY_SEPARATOR);
		    $cntB = substr_count($b->file, DIRECTORY_SEPARATOR);

		    $aa = (($a->isDir || $cntA > 0) ? '1':'0') . $a->file;
		    $bb = (($b->isDir || $cntB > 0) ? '1':'0') . $b->file;

		    return strcasecmp($aa, $bb);
	    });

	    $this->transientState = array( 'result' => array(
		    'total' => $total,
		    'acceptable' => $acceptableCount,
		    'unacceptable' => (int)($total - $acceptable),
		    'files' => $files,
	    ));
    }

}