<?php
namespace integrityChecker\Tests;

class TestFactory
{
    /**
     * @var array
     */
    private $tests;

    /**
     * @var array
     */
    private $instances = array();

    /**
     * @var \integrityChecker\Settings
     */
    protected $settings;

    /**
     * @var State
     */
    private $state;

    /**
     * @var \integrityChecker\ApiClient
     */
    private $apiClient;

    /**
     * TestFactory constructor.
     *
     * @param $tests
     * @param \integrityChecker\Settings            $settings
     * @param \integrityChecker\State               $state
     * @param \integrityChecker\ApiClient           $apiClient
     */
    public function __construct($tests, $settings, $state, $apiClient)
    {
        $this->tests             = $tests;
        $this->settings          = $settings;
        $this->state             = $state;
        $this->apiClient         = $apiClient;
    }

    /**
     * @param $testName
     *
     * @return bool
     *
     */
    public function hasTest($testName)
    {
        return isset($this->tests[$testName]);
    }

    /**
     * @return array
     */
    public function getTestNames()
    {
        return array_keys($this->tests);
    }

    /**
     * @param $testName
     *
     * @return BaseTest
     */
    public function getTestObject($testName)
    {
        if (isset($this->tests[$testName])) {

            if (!isset($this->instances[$testName])) {
                $class = $this->tests[$testName];
                $this->instances[$testName] = new $class($this->settings, $this->state, $this->apiClient, $this);
            }

            return $this->instances[$testName];
        }

        return null;
    }


}