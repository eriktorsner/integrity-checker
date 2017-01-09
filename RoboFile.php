<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    public function hello($world)
    {
        $this->say("Hello $world");
    }

    public function build()
    {
        $this->stopOnFail(true);
        $test = $this->test();

        $this->say("All good");

    }

    public function test()
    {
        return $this->taskPHPUnit()
             ->bootstrap('tests/bootstrap.php')
             ->run();
    }

    public function svn()
    {
        exec('rsync -r --exclude=".git/" wp-content/plugins/geckopress/ svnrepo/trunk/');
        exec('rsync -r --exclude=".git/" pluginmeta/*.png svnrepo/assets/');
        exec('rsync -r --exclude=".git/" pluginmeta/*.txt svnrepo/trunk/');
        unlink('svnrepo/trunk/composer.lock');
    }

    private function findGitTag()
    {
        
    }
}