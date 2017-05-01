<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    private $svnRemote = 'https://eriktorsner@plugins.svn.wordpress.org/integrity-checker';
    private $gitRemote = 'git@github.com:eriktorsner/integrity-checker.git';
    private $slug = 'integrity-checker';
    private $excludeSvn = ['build', 'tests', 'phpunit.xml', 'RoboFile.php', 'composer.lock', 'travis',
        '.travis.yml', '.idea'];
    private $svnDir;
    private $buildBase;

    public function __construct()
    {
        $this->svnDir = __DIR__ . '/build/svnrepo/' . $this->slug;
        $this->buildBase = __DIR__ . '/build/' . $this->slug;
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
    public function publish($version)
    {
        $this->stopOnFail(true);

        // Checkout a pure version from git
        $this->gitClone($version);

        // Ensure tests are OK
        $this->test();

        // Ensure we have latest SVN checked out
        $this->svnCheckout();

        // ensure version is fixed in all files
        $this->versionfix($version);

        // Copy files to svn trunk
        $this->copyToSvnTrunk($version);

        // Create the tag (copy from trunk)
        $this->createSvnTag($version);

        // Commit to repo;
        $this->svnCommit($version);

        $this->say("All done");

    }

    public function testpublish($version)
    {
        $this->stopOnFail(true);

        // Checkout a pure version from git
        // Testpublish always use master
        $this->gitClone();

        // Ensure tests are OK
        $this->test();

        // Ensure we have latest SVN checked out
        $this->svnCheckout();

        // ensure version is fixed in all files
        $this->versionfix($version);

        // Copy files to svn trunk
        $this->copyToSvnTrunk($version);

        // Create the tag (copy from trunk)
        $this->createSvnTag($version);

    }

    /**
     * Run all tests
     *
     * @return mixed
     */
    public function test()
    {
        return $this->taskPHPUnit()
            ->args(['--testsuite=Unit'])
            ->dir("{$this->buildBase}-test")
            ->run();
    }

    /**
     * @param $version
     */
    public function versionfix($version)
    {
        $this->taskReplaceInFile("{$this->buildBase}/readme.txt")
             ->regex('~Stable tag:\s.*~')
             ->to('Stable tag: ' . $version)
             ->run();

        $this->taskReplaceInFile("{$this->buildBase}/{$this->slug}.php")
             ->regex('~Version:\s*.*~')
             ->to('Version:           ' . $version)
             ->run();

        $this->taskReplaceInFile("{$this->buildBase}/{$this->slug}.php")
             ->regex('~\$pluginVersion\s*\=.*;~')
             ->to("\$pluginVersion = '$version';")
             ->run();

    }

    private function gitClone($version = 'master')
    {
        exec("rm -rf {$this->buildBase}");
        exec("rm -rf {$this->buildBase}-test");
        $cmd = "git clone {$this->gitRemote}";
        if ($version != 'master') {
            $cmd .= " --branch $version";
        }

        $cmd.= " --single-branch {$this->buildBase}";
        exec($cmd);
        exec("cp -a build/{$this->slug} {$this->buildBase}-test");

        exec("composer install --no-dev -d {$this->buildBase}");
        exec("composer install -d {$this->buildBase}-test");
    }

    /**
     * Get a fresh copy of the SVN repo
     */
    private function svnCheckout()
    {
        $svnBase = dirname($this->svnDir);
        exec("rm -rf $svnBase");
        exec("mkdir -p $svnBase");

        $this->say("Cleaned and recreated $svnBase");

        $this->taskSvnStack()
             ->checkout($this->svnRemote)
             ->dir($svnBase)
             ->run();
    }


    /**
     * @param $version
     */
    private function copyToSvnTrunk($version)
    {
        $exclude = array_merge($this->excludeSvn, ['.git', '.gitignore', 'svnrepo', 'assets']);
        $existing = array_diff(scandir("{$this->buildBase}"), ['.', '..']);

        // Blank out trunk and assets
        exec("rm -rf {$this->svnDir}/trunk");
        exec("rm -rf {$this->svnDir}/assets");
        exec("mkdir {$this->svnDir}/trunk");
        exec("mkdir {$this->svnDir}/assets");

        // Copy all files to trunk
        foreach ($existing as $file) {
            if (!in_array(trim(basename($file)), $exclude)) {
                $this->say("rsync -ra {$this->buildBase}/$file {$this->svnDir}/trunk");
                exec("rsync -ra {$this->buildBase}/$file {$this->svnDir}/trunk");
            }
        }

        // Copy to assets
        $this->say("rsync -ra {$this->buildBase}/assets/ {$this->svnDir}/assets");
        exec("rsync -ra {$this->buildBase}/assets/ {$this->svnDir}/assets");

        // add all files to svn
        $this->taskSvnStack()->add("--force trunk/*")->dir($this->svnDir)->run();
        $this->taskSvnStack()->add("--force assets/*")->dir($this->svnDir)->run();
    }

    private function createSvnTag($version)
    {

        $this->taskExec("svn cp trunk tags/$version")
             ->dir($this->svnDir)
             ->run();
    }


    private function svnCommit($version)
    {
        $this->taskSvnStack()->commit("Version $version")->dir($this->svnDir)->run();
    }

    private function latestSvnTag()
    {
        $tags = scandir($this->svnDir . '/tags');
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