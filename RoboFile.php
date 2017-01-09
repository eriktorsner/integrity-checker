<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    private $svnRemote = 'https://eriktorsner@plugins.svn.wordpress.org/integrity-checker';
    private $slug = 'integrity-checker';

    public function hello($world)
    {
        $this->say("Hello $world");
    }

    /**
     * @param $version
     */
    public function tag($version)
    {
        $ret = $this->taskGitStack()->exec('status')->run();
        print_r($ret);
    }

    public function publish()
    {
        $this->stopOnFail(true);
        // Ensure tests are OK
        $this->test();

        // Ensure we have latest SVN version
        $this->svnCheckout();

        // Ensure the git tag is newer/higher than the one in SVN
        $gitVersion = exec('git describe --abbrev=0 --tags');
        $svnVersion = $this->latestSvnTag();
        if (version_compare($gitVersion , $svnVersion) != 1) {
            $this->yell("Latest git tag is not higher than latest svn tag. Aborting", 40, 'red');
            return;
        }

        // Copy files to svn trunk
        $this->copyToSvnTrunk($gitVersion);

        $this->say($gitVersion  . ' ' . $svnVersion);
        $this->say("All good");

    }

    public function test()
    {
        return $this->taskPHPUnit()
             ->bootstrap('tests/bootstrap.php')
             ->run();
    }

    public function svnCheckout()
    {
        $svnDir = __DIR__ . '/svnrepo';
        exec("rm -rf $svnDir");
        mkdir($svnDir);

        $this->taskSvnStack()
             ->checkout($this->svnRemote)
             ->dir($svnDir)
             ->run();
    }


    private function copyToSvnTrunk($version)
    {
        $svnDir = __DIR__ . '/svnrepo';
        $exclude = ['build', 'svnrepo', 'tests', 'phpunit.xml', 'RoboFile.php', 'composer.lock', 'assets'];
        $exclude = array_merge($exclude, ['.git', '.gitignore']);
        $existing = array_diff(scandir(__DIR__), ['.', '..']);

        // Copy all files to trunk
        foreach ($existing as $file) {
            if (!in_array(trim($file), $exclude)) {
                $this->say("rsync -ra $file $svnDir/{$this->slug}/trunk");
                exec("rsync -ra $file $svnDir/{$this->slug}/trunk");
            }
        }
        $this->say("rsync -ra assets $svnDir/{$this->slug}/assets");
        exec("rsync -ra assets $svnDir/{$this->slug}");

        // Ensure we have correct version info
        $this->taskReplaceInFile('readme.txt')->regex('Stable tag: 0.9.1');

        // add all files
        $this->taskSvnStack()->add("trunk/*")->dir("$svnDir/{$this->slug}")->run();
        $this->taskSvnStack()->add("assets/*")->dir("$svnDir/{$this->slug}")->run();

        // commit them with proper tag
        $this->taskSvnStack()->commit("“Version $version")->dir("$svnDir/{$this->slug}")->run();

        /*exec("svn cp trunk tags");*/
    }

    private function latestSvnTag()
    {
        $svnDir = __DIR__ . '/svnrepo';
        $tags = scandir($svnDir . '/' . $this->slug . '/tags');
        $tags = array_diff($tags, ['.', '..']);

        sort($tags);
        return end($tags);
    }

    public function versionfix($version)
    {
        $this->taskReplaceInFile('readme.txt')
             ->regex('~Stable tag:\s.*~')
             ->to('Stable tag: ' . $version)
             ->run();
        $this->taskReplaceInFile($this->slug . '.php')
             ->regex('~Version:\s*.*~')
             ->to('Version:           ' . $version)
             ->run();
    }
}