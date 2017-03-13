<?php
namespace WPChecksum;

class FolderChecksum
{
    /**
     * Recurse into sub folders?
     *
     * @var bool
     */
    public $recursive = true;

    /**
     * Calculate MD5 hashes or not
     *
     * @var bool
     */
    public $calcHash = true;

    /**
     * Return information about folders?
     *
     * @var bool
     */
    public $includeFolderInfo = false;

    /**
     * Return information about owner and group?
     *
     * @var bool
     */
    public $includeOwner = false;

    /**
     * Max file size for checksum calculation
     * default: 20M
     *
     * @var int
     */
    public $maxFileSize = 20971520;

    /**
     * Path to scan
     *
     * @var string
     */
    private $path;

    /**
     * Alternate base folder
     *
     * @var string
     */
    private $basePath;

    /**
     * Patterns to ignore from scan. Evaluated with fnmatch()
     *
     * @var array
     */
    private $ignore = array();

    /**
     * Keeps track of (cached) system user information
     * as returned from posix_getpwuid
     *
     * @var array
     */
    private $systemUsers = array();

    /**
     * Keeps track of (cached) system group information
     * as returned from posix_getgrgid
     *
     * @var array
     */
    private $systemGroups = array();


    /**
     * FolderChecksum constructor.
     *
     * @param string $path target folder
     * @param string $base alternate base folder
     */
    public function __construct($path, $base = '')
    {
        $this->ignore = array();
        $this->path = $path;
        $this->basePath = $path;
        if (strlen($base) > 0) {
            $this->basePath = $base;
        }
    }

    public function addIgnorePattern($pattern)
    {
        $this->ignore = array_merge($this->ignore, (array)$pattern);
    }

    public function scan()
    {
        $flat = $this->recIterateDir($this->path, $this->basePath);
        $output =  $this->flatToJson($flat);
        return $output;
    }

    public function scanRaw()
    {
        return $this->recIterateDir($this->path, $this->basePath);
    }

    private function flatToJson($flat)
    {
        $out = new \stdClass();
        $checksums = array();
        $rows = explode("\n", $flat);
        foreach($rows as $row) {
            if (trim($row) == '') {
                continue;
            }

            $cols = explode("\t", $row);
            // Skip directories
            if ($cols[4] == '1' || count($cols) == 1) {
                continue;
            }

            $obj = new \stdClass();
            $obj->date = $cols[1];
            $obj->size = $cols[2];
            $obj->hash = $cols[3];
            $obj->mode = $cols[4];
            $obj->isDir = $cols[5];
            $obj->isLink = $cols[7];
            $obj->owner = $cols[8];
            $obj->group = $cols[9];
            $checksums[$cols[0]] = $obj;
        }

        $out->status = 200;
        $out->checksums = $checksums;
        return $out;
    }

    private function recIterateDir($dir, $base)
    {
        $out = '';
        $iterator = new \FilesystemIterator(
            $dir,
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
        );
        foreach($iterator AS $file) {
            try {
                if ($this->fnInArray("$dir/$file", $this->ignore)) {
                    continue;
                }

                if (is_file($file)) {
                    $out .= $this->fileInfo2String($file, $base) . "\n";
                    continue;
                }

                if ($this->includeFolderInfo) {
                    $out .= $this->fileInfo2String($file, $base) . "\n";
                }

                if ($this->recursive) {
                    $out .= $this->recIterateDir($file, $base);
                }
            }
            catch(Throwable $e){ continue; }
            catch(Exception $e){ continue; }
        }

        return $out;
    }

    private function fileInfo2String($file, $base)
    {
        $sum = 0;
        $owner = '';
        $group = '';
        $isDir = is_dir($file);
        $stat = stat($file);

        $base = rtrim($base, '/') . '/';
        $relfile = substr($file, strlen($base));

        if ($this->calcHash) {
            if ($stat['size'] < $this->maxFileSize) {
                $sum = md5_file($file);
            } else {
                $i = 0;
            }
        }

        if ($this->includeOwner) {
            $ownerId = $stat['uid'];
            $groupId = $stat['gid'];
            if (!isset($this->systemUsers[$ownerId])) {
                $this->systemUsers[$ownerId] = posix_getpwuid($ownerId);
            }
            if (!isset($this->systemGroups[$groupId])) {
                $this->systemGroups[$groupId] = posix_getgrgid($groupId);
            }

            $owner = $this->systemUsers[$ownerId] ? $this->systemUsers[$ownerId]['name'] : '';
            $group = $this->systemGroups[$groupId] ? $this->systemGroups[$groupId]['name'] : '';
        }

        $row =  array(
            $relfile,
            $stat['mtime'],
            $isDir ? 0 : $stat['size'],
            $isDir ? 0 : $sum,
            substr(decoct($stat['mode']), -4),
            (int) $isDir,
            (int) is_file($file),
            (int) is_link($file),
            $owner,
            $group,
        );

        return join("\t", $row);
    }

    private function fnInArray($needle, $haystack)
    {
        # this function allows wildcards in the array to be searched
        $needle = substr($needle, strlen($this->basePath));
        foreach ($haystack as $value) {
            if (true === fnmatch($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}