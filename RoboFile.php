<?php
/**
 * This is Integrity Checker's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     * @var string
     */
    private $slug = 'integrity-checker';

    /**
     * @var string
     */
    private $svnRemote = 'https://eriktorsner@plugins.svn.wordpress.org/integrity-checker';

    /**
     * @var string
     */
    private $gitRemote = 'git@github.com:eriktorsner/integrity-checker.git';


    /**
     * Files/folders that should NOT be copied to the svn repo
     * (assets are handled separately)
     *
     * @var array
     */
    private $excludeSvn = ['build', 'tests', 'phpunit.xml', 'RoboFile.php', 'composer.lock', 'travis',
        '.travis.yml', '.idea', '.git', '.gitignore', 'svnrepo', 'assets'];

    /**
     * @var string
     */
    private $svnDir;

    /**
     * @var string
     */
    private $buildBase;

    /**
     * RoboFile constructor.
     */
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
     * Checkout the relevant
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

    /**
     * For dry running the publish process.
     * @param $version
     */
    public function testpublish($version)
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

        // Notably, when testing, we never commit to the
        // svn repo

    }

    /**
     * Run all tests (inside the build folder)
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
     * Ensure we have the correct versions in
     * plugin base file and readme.txt
     *
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

    /**
     * Clone code from github into the build folder
     * Make 2 copies:
     * (1) intended for publishing to the WordPress repo without dev dependencies.
     * (2) intended for running local tests, including dev dependencies.
     *
     * @param string $version
     */
    private function gitClone($version = 'master')
    {
        exec("rm -rf {$this->buildBase}");
        exec("rm -rf {$this->buildBase}-test");
        $cmd = "git clone {$this->gitRemote}";
        if ($version != 'master') {
            $cmd .= " --branch Release/$version";
        }

        $cmd.= " --single-branch {$this->buildBase}";
        exec($cmd);
        exec("cp -a build/{$this->slug} {$this->buildBase}-test");

        exec("composer install --no-dev -d {$this->buildBase}");
        exec("composer install -d {$this->buildBase}-test");
    }

    /**
     * Get a fresh copy of the SVN repo into the build folder
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
     * Copy all files from build/$slug folder into the svn trunk
     *
     * @param $version
     */
    private function copyToSvnTrunk($version)
    {
        $existing = array_diff(scandir("{$this->buildBase}"), ['.', '..']);

        // Blank out trunk and assets
        exec("rm -rf {$this->svnDir}/trunk");
        exec("rm -rf {$this->svnDir}/assets");
        exec("mkdir {$this->svnDir}/trunk");
        exec("mkdir {$this->svnDir}/assets");

        // Copy all files to trunk
        foreach ($existing as $file) {
            if (!in_array(trim(basename($file)), $this->excludeSvn)) {
                $this->say("rsync -ra {$this->buildBase}/$file {$this->svnDir}/trunk");
                exec("rsync -ra {$this->buildBase}/$file {$this->svnDir}/trunk");
            }
        }

        // Copy to assets separately
        $this->say("rsync -ra {$this->buildBase}/assets/ {$this->svnDir}/assets");
        exec("rsync -ra {$this->buildBase}/assets/ {$this->svnDir}/assets");

        // add all files to svn
        $this->taskSvnStack()->add("--force trunk/*")->dir($this->svnDir)->run();
        $this->taskSvnStack()->add("--force assets/*")->dir($this->svnDir)->run();

        // find deleted files
        $result = $this->taskExec("svn status {$this->svnDir} | grep \"!\"")->run();
        $files = explode("\n", trim($result->getMessage()));
        foreach ($files as $file) {
            $file = trim(ltrim($file, '!'));
            $this->taskExec("svn delete {$file}")->run();
        }
    }


    /**
     * Create a new tag in SVN and copy the current trunk.
     *
     * @param $version
     */
    private function createSvnTag($version)
    {

        $this->taskExec("svn cp trunk tags/$version")
             ->dir($this->svnDir)
             ->run();
    }


    /**
     * Commit all changes in the svn folder back to the repo
     *
     * @param $version
     */
    private function svnCommit($version)
    {
        $this->taskSvnStack()->commit("Version $version")->dir($this->svnDir)->run();
    }

    /**
     * @return mixed
     */
    private function latestSvnTag()
    {
        $tags = scandir($this->svnDir . '/tags');
        $tags = array_diff($tags, ['.', '..']);

        sort($tags);
        return end($tags);
    }

    /**
     * @param $version
     *
     * @return bool
     */
    private function verifyVersions($version)
    {
        $readMe = stripos(file_get_contents('readme.txt'), $version);
        $pluginHeader = stripos(file_get_contents($this->slug . '.php'), $version);
        $ret = ($readMe !== false && $pluginHeader !== false);

        return $ret;
    }
}