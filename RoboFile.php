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
    private $excludeSvn = ['build', 'tests', 'phpunit.xml', 'RoboFile.php', 'composer.lock'];

    public function hello($world)
    {
        $this->say("Hello $world");
    }

    /**
     * Tag the current version in git and push the
     * new tag to the remote (origin)
     *
     * @param $version
     */
    public function tag($version)
    {
        $this->versionfix($version);
        $this->taskGitStack()
            ->add(join(' ', ['readme.txt ', "{$this->slug}.php"]))
            ->commit('updating version')
            ->push('origin')
            ->run();

        $this->taskGitStack()
             ->tag($version)
             ->push('origin',$version)
             ->run();
    }

    /**
     * Grab the current version of the git working directory (I know)
     * and publish it as a new version on the WordPress repo named after
     * the current latest git tag.
     *
     * Pre flight checks:
     * 1. Do all the tests pass?
     * 2. Are the readme and plugin header file updated with the correct version?
     * 3. Is the wordpress/svn version older/lower than the git tag?
     *
     */
    public function publish()
    {
        $this->stopOnFail(true);
        // Ensure tests are OK
        $this->test();

        // Git version
        $gitVersion = exec('git describe --abbrev=0 --tags');

        // Check that git tag matches wp files version
        if (!$this->verifyVersions($gitVersion)) {
            $this->yell(
                "Latest git version ($gitVersion) doesn't match plugin version info. Aborting",
                40,
                'red');
            return;
        }

        // Ensure we have latest SVN checked out
        $this->svnCheckout();

        // Ensure the git tag is newer/higher than the one in SVN
        $svnVersion = $this->latestSvnTag();
        if (version_compare($gitVersion , $svnVersion) != 1) {
            $this->yell("Latest git tag is not higher than latest svn tag. Aborting", 40, 'red');
            return;
        }

        // Copy files to svn trunk
        $this->copyToSvnTrunk($gitVersion);

        $this->say("All done");

    }

    /**
     * Run all tests
     *
     * @return mixed
     */
    public function test()
    {
        return $this->taskPHPUnit()
             ->bootstrap('tests/bootstrap.php')
             ->run();
    }

    /**
     * @param $version
     */
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

    /**
     * Get a fresh copy of the SVN repo
     */
    private function svnCheckout()
    {
        $svnDir = __DIR__ . '/svnrepo';
        exec("rm -rf $svnDir");
        mkdir($svnDir);

        $this->taskSvnStack()
             ->checkout($this->svnRemote)
             ->dir($svnDir)
             ->run();
    }


    /**
     * @param $version
     */
    private function copyToSvnTrunk($version)
    {
        $svnDir = __DIR__ . '/svnrepo/' . $this->slug;

        $exclude = array_merge($this->excludeSvn, ['.git', '.gitignore', 'svnrepo', 'assets']);
        $existing = array_diff(scandir(__DIR__), ['.', '..']);

        // Copy all files to trunk
        foreach ($existing as $file) {
            if (!in_array(trim($file), $exclude)) {
                $this->say("rsync -ra $file $svnDir/trunk");
                exec("rsync -ra $file $svnDir/trunk");
            }
        }
        $this->say("rsync -ra assets $svnDir/assets");
        exec("rsync -ra assets $svnDir");

        // add all files to svn
        $this->taskSvnStack()->add("--force trunk/*")->dir("$svnDir")->run();
        $this->taskSvnStack()->add("--force assets/*")->dir("$svnDir")->run();

        // commit them with proper tag
        $this->taskSvnStack()->commit("Version $version")->dir("$svnDir")->run();

        $this->taskExec("svn cp trunk tags/$version")
            ->dir($svnDir)
            ->run();

        $this->taskSvnStack()->commit("tagging version $version")->dir("$svnDir")->run();

    }

    private function latestSvnTag()
    {
        $svnDir = __DIR__ . '/svnrepo/' . $this->slug;
        $tags = scandir($svnDir . '/tags');
        $tags = array_diff($tags, ['.', '..']);

        sort($tags);
        return end($tags);
    }

    private function verifyVersions($version)
    {
        $readMe = stripos(file_get_contents('readme.txt'), $version);
        $pluginHeader = stripos(file_get_contents($this->slug . '.php'), $version);
        $ret = ($readMe !== false && $pluginHeader !== false);

        return $ret;
    }
}