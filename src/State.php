<?php
namespace integrityChecker;

/**
 * Class State
 * @package integrityChecker
 */
class State
{
    /**
     * @param $testName
     * @return array|mixed|void
     */
    public function getTestState($testName)
    {
        $procStatus = get_option("integrity-checker_status_$testName", false);
        if (!$procStatus) {
            $procStatus = $this->noState();
        }
        $procStatus->startedIso = date('Y-m-d H:i:s', $procStatus->started);
        $procStatus->finishedIso = date('Y-m-d H:i:s', $procStatus->finished);

        return $procStatus;
    }

    /**
     * Store state in wp_options
     *
     * @param string        $testName
     * @param array|object  $state
     */
    public function updateTestState($testName, $state)
    {
        update_option("integrity-checker_status_$testName", $state);
    }

	/**
	 * @param string $testName
	 * @param mixed $result
	 */
    public function storeTestResult($testName, $result)
    {
        $current = get_option("integrity-checker_result_$testName", array());
        $current[] = $result;

        // keep only the 5 last results
        $result = array_slice($current, -5);
        update_option("integrity-checker_result_{$testName}", $result);
    }

    /**
     * Get test result from WP_Options table
     *
     * @param string $testName
     * @param bool $escape
     *
     * @return mixed|void
     */
    public function getTestResult($testName, $escape = false)
    {
        $allResults = get_option("integrity-checker_result_$testName", array());
	    usort($allResults, function($a, $b) {
		   return $a['ts'] - $b['ts'];
	    });
	    $ret = end($allResults);

	    if ($escape) {
		    $this->escapeObjectStrings($ret);
	    }

        return $ret;
    }

    /**
     * @return object
     */
    private function noState()
    {
        return (object)array(
            'state' => 'never_started',
            'started' => null,
            'finished' => null,
        );
    }

	/**
	 * Walk through the object and ensure all strings are escaped
	 *
	 * @param $obj
	 */
    private function escapeObjectStrings(&$obj)
    {
	    if (!$obj) {
		    return;
	    }
		foreach ($obj as $key => &$item) {
			if (is_string($item)) {
				$item = esc_html($item);
			}

			if (is_object($item) || is_array($item)) {
				$this->escapeObjectStrings($item);
			}
		}
    }

}