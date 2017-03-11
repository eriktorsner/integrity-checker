<?php

namespace integrityChecker;

class TestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstuct()
    {
        $tests = array(
            'checksum' => 'integrityChecker\Tests\Checksum',
            'scanall'  => 'integrityChecker\Tests\ScanAll',
            'files'    => 'integrityChecker\Tests\Files',
            'settings' => 'integrityChecker\Tests\Settings',
        );

        $dummy = new \stdClass();
        $testFactory = new Tests\TestFactory($tests, $dummy, $dummy, $dummy);

        $this->assertTrue($testFactory->hasTest('checksum'));
        $this->assertTrue($testFactory->hasTest('scanall'));
        $this->assertTrue($testFactory->hasTest('files'));
        $this->assertTrue($testFactory->hasTest('settings'));
        $this->assertFalse($testFactory->hasTest('notatest'));

        $this->assertEquals(4, count($testFactory->getTestNames()));

        $test = $testFactory->getTestObject('files');

        $this->assertEquals('integrityChecker\Tests\Files', get_class($test));
        $this->assertEquals(null, $testFactory->getTestObject('notatest'));

    }
}