<?php

use integrityChecker\Pimple\ServiceProviderInterface;
use integrityChecker\Pimple\Container;

class RuntimeProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $slug = 'integrity-checker';
        $tests = array(
            'checksum' => 'integrityChecker\Tests\Checksum',
            'scanall'  => 'integrityChecker\Tests\ScanAll',
            'files'    => 'integrityChecker\Tests\Files',
            'settings' => 'integrityChecker\Tests\Settings',
        );

        $pimple['apiClient'] = function ($pimple) {
            return new \integrityChecker\ApiClient();
        };

        $pimple['settings'] = function ($pimple) use($slug) {
            return new \integrityChecker\Settings(
                $slug,
                $pimple['apiClient']
            );
        };

        $pimple['state'] = function ($pimple) use($slug) {
            return new \integrityChecker\State($slug);
        };

        $pimple['testfactory'] = function($pimple) use($tests) {
            return new integrityChecker\Tests\TestFactory(
                $tests,
                $pimple['settings'],
                $pimple['state'],
                $pimple['apiClient']
            );
        };

        $pimple['backgroundProcess'] = function ($pimple) {
            return new \integrityChecker\BackgroundProcess($pimple['testfactory']);
        };

        $pimple['adminUiHooks'] = function ($pimple) {
            return new \integrityChecker\AdminUIHooks($pimple['settings'], $pimple['state']);
        };

        $pimple['adminPage'] = function ($pimple) {
            return new \integrityChecker\Admin\AdminPage($pimple['settings']);
        };

        $pimple['fileDiff'] = function ($pimple) {
            return new \integrityChecker\FileDiff($pimple['apiClient']);
        };

        $pimple['interityChecker'] = function ($pimple) {
            return new \integrityChecker\integrityChecker(
                $pimple['settings'],
                $pimple['adminUiHooks'],
                $pimple['adminPage'],
                $pimple['rest'],
                $pimple['backgroundProcess']
            );
        };

        $pimple['process'] = function ($pimple) use($tests) {
            return new \integrityChecker\Process(
                $pimple['testfactory'],
                $pimple['settings'],
                $pimple['state'],
                $pimple['backgroundProcess']
            );
        };

        $pimple['rest'] = function ($pimple)  {
            return new \integrityChecker\Rest(
                $pimple['settings'],
                $pimple['apiClient'],
                $pimple['process'],
                $pimple['fileDiff']
            );
        };
    }
}