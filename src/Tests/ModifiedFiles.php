<?php
namespace integrityChecker\Tests;
use integrityChecker\State;
use integrityChecker\integrityChecker;
use integrityChecker\Diff\Diff;
use integrityChecker\Diff\RendererUnified;

/**
 * Class Permissions
 * @package integrityChecker\Tests
 */
class ModifiedFiles extends BaseTest
{
    /**
     * @var string
     */
    public $name = 'modifiedfiles';

    /**
     * @var array
     */
    private $ignorePatterns;

    /**
     * Permissions constructor.
     *
     * @param null $session
     */
    public function __construct($session) {
        parent::__construct( $session );

    }

    /**
     * @param $request
     */
    public function start($request)
    {
        $limit = 900;
        $payload = json_decode($request->get_body());
        if (isset($payload->source) && $payload->source == 'manual') {
            $limit = 120;
        }

        $bgProcess = $this->getBackgroundProcess(new ScanAll(null), $request, $limit);
        $this->session = $bgProcess->session;

        parent::start($request);
        $this->transientState = '';
        $offset = $bgProcess->lastQueuePriority();

        $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'shapeResult'), $offset + 20);
        $bgProcess->addJob((object)array('class' => __CLASS__, 'method' => 'finish'), $offset + 99);

        $bgProcess->process(true);
    }

    /**
     * @param $job
     */
    public function shapeResult($job)
    {
        $files = array();
        $total = 0;
        $acceptableCount = 0;
        $plugin = integrityChecker::getInstance();
        $slug = $plugin->getPluginSlug();

        // Get the lost of all files
        $objState = new State();
        $transientState = $objState->getTestResult('scanall');
        $rows = array();
        if (isset($transientState['files']) && strlen($transientState['files']) > 0) {
            $rows = explode("\n", rtrim($transientState['files'], "\n"));
        }

        // Get the checkpoint
        $checkpoint = get_option("{$slug}_files_checkpoint", false);
        $checkpointFiles = gzuncompress(base64_decode($checkpoint->data));

        // Get the diff
        $options = array('context' => 0,);
        $differ = new Diff($rows, explode("\n", $checkpointFiles), $options);
        $opCodes = $differ->getGroupedOpcodes();
        $txtDiff = $differ->Render(new RendererUnified($options));


        usort($files, function($a, $b) {
            $cntA = substr_count($a->file, DIRECTORY_SEPARATOR);
            $cntB = substr_count($b->file, DIRECTORY_SEPARATOR);

            $aa = (($a->isDir || $cntA > 0) ? '1':'0') . $a->file;
            $bb = (($b->isDir || $cntB > 0) ? '1':'0') . $b->file;

            return strcasecmp($aa, $bb);
        });

        $this->transientState = array('result' => array(
            'total' => $total,
            'acceptable' => $acceptableCount,
            'unacceptable' => (int)($total - $acceptableCount),
            'files' => $files,
        ));
    }

}