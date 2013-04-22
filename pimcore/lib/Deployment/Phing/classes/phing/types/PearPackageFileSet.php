<?php
/**
 * Part of phing, the PHP build tool
 *
 * PHP version 5
 *
 * @category Types
 * @package  phing.types
 * @author   Christian Weiske <cweiske@cweiske.de>
 * @license  LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @version  SVN: $Id: 5ba010b83645d0ea709761a3d8260fc013239458 $
 * @link     http://www.phing.info/
 */
require_once 'phing/types/FileSet.php';
require_once 'PEAR/Config.php';
require_once 'phing/util/PearPackageScanner.php';

/**
 * Fileset that contains files of an installed PEAR package.
 * It can be used to package up PEAR package dependencies in own
 * release files (zip, tgz, phar).
 *
 * @internal
 * A normal fileset is used that way in CopyTask, rSTTask:
 * <code>
 *  $ds = $fs->getDirectoryScanner($project);
 *  $fromDir  = $fs->getDir($project);
 *  $srcFiles = $ds->getIncludedFiles();
 *  $srcDirs  = $ds->getIncludedDirectories();
 * </code>
 * The scanner is used as follows:
 * <code>
 *  $ds->getBaseDir()
 *  $ds->scan()
 * </code>
 *
 * @category Types
 * @package  phing.types
 * @author   Christian Weiske <cweiske@cweiske.de>
 * @license  LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @link     http://www.phing.info/
 */
class PearPackageFileSet extends FileSet
{
    /**
     * Name of channel the package is from, e.g. "pear.php.net".
     *
     * @var string
     */
    protected $channel;

    /**
     * Package name to get files from, e.g. "Console_CommandLine"
     *
     * @var string
     */
    protected $package;

    /**
     * Use files of that role only.
     * Multiple roles are not supported, and you always have to specify one.
     *
     * @var string
     */
    protected $role = 'php';

    /**
     * Prefix to prepend to the file paths in the zip
     */
    protected $prefix;

    /**
     * Full path to a PEAR config file.
     * If none provided, default one is used.
     */
    protected $config;

    /**
     * @var PearPackageScanner instance
     */
    protected $pps;


    /**
     * Creates and returns the pear package scanner.
     * Scanner already has scan() called.
     *
     * @param Project $project Current phing project
     *
     * @return PearPackageScanner
     */
    public function getDirectoryScanner(Project $project)
    {
        if ($this->isReference()) {
            $obj = $this->getRef($project);
            return $obj->getDirectoryScanner($project);
        }

        $this->loadPearPackageScanner();
        return $this->pps;
    }

    /**
     * Returns the base directory all package files are relative to
     *
     * @return PhingFile Base directory
     */
    public function getDir()
    {
        if ($this->pps === null) {
            $this->loadPearPackageScanner();
        }
        return new PhingFile((string) $this->pps->getBaseDir());
    }

    /**
     * Loads the package scanner instance into $this->pps
     *
     * @return void
     */
    protected function loadPearPackageScanner()
    {
        $this->pps = new PearPackageScanner();
        $this->pps->setPackage($this->package);
        $this->pps->setChannel($this->channel);
        $this->pps->setRole($this->role);
        $this->pps->setConfig($this->config);
        $this->pps->scan();
    }

    /**
     * Sets the package name.
     * If no channel is given, "pear.php.net" is used.
     *
     * @param string $package Single package name, or "channel/name" combination
     *
     * @return void
     */
    public function setPackage($package)
    {
        $parts = explode('/', $package);
        if (count($parts) > 2) {
            throw new BuildException('Invalid package name: ' . $package);
        }

        if (count($parts) == 1) {
            $this->channel = 'pear.php.net';
            $this->package = $parts[0];
        } else {
            $this->channel = $parts[0];
            $this->package = $parts[1];
        }
    }

    /**
     * Sets the role of files that should be included.
     * Examples are php,doc,script
     *
     * @param string $role PEAR file role
     *
     * @return void
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * Sets the full path to the PEAR configuration file
     *
     * @param string $config Configuration file
     *
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
}
