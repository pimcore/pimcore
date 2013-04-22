<?php
/**
 * Part of phing, the PHP build tool
 *
 * PHP version 5
 *
 * @category Util
 * @package  phing.util
 * @author   Christian Weiske <cweiske@cweiske.de>
 * @license  LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @version  SVN: $Id: e549026313edf53c67f495489f671cf0b71df80d $
 * @link     http://www.phing.info/
 */
require_once 'phing/util/DirectoryScanner.php';
require_once 'PEAR/Config.php';

/**
 * Scans for files in a PEAR package.
 *
 * @category Util
 * @package  phing.util
 * @author   Christian Weiske <cweiske@cweiske.de>
 * @license  LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @link     http://www.phing.info/
 */
class PearPackageScanner extends DirectoryScanner
{
    protected $packageInfo;
    protected $role = 'php';
    protected $config;
    protected $package;
    protected $channel = 'pear.php.net';

    /**
     * Sets the name of the PEAR package to get the files from
     *
     * @param string $package Package name without channel
     *
     * @return void
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * Sets the name of the package channel name
     *
     * @param string $channel package channel name or alias
     *
     * @return void
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
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
        if ($config != '' && !file_exists($config)) {
            throw new BuildException(
                'PEAR configuration file "' . $config . '" does not exist'
            );
        }

        $this->config = $config;
    }

    /**
     * Sets the role of files that should be included.
     * Examples are php,doc,script
     *
     * @param string $role PEAR file role
     *
     * @return void
     *
     * @internal
     * We do not verify the role against a hardcoded list since that
     * would break packages with additional roles.
     */
    public function setRole($role)
    {
        if ($role == '') {
            throw new BuildException('A non-empty role is required');
        }

        $this->role = $role;
    }

    /**
     * Loads the package information.
     *
     * @return void
     *
     * @uses $packageInfo
     */
    protected function init()
    {
        if (!$this->packageInfo) {
            $this->packageInfo = $this->loadPackageInfo();
        }
    }

    /**
     * Loads and returns the PEAR package information.
     *
     * @return PEAR_PackageFile_v2 Package information object
     *
     * @throws BuildException When the package does not exist
     */
    protected function loadPackageInfo()
    {
        $cfg = PEAR_Config::singleton($this->config);
        $reg = $cfg->getRegistry();
        if (!$reg->packageExists($this->package, $this->channel)) {
            throw new BuildException(
                sprintf(
                    'PEAR package %s/%s does not exist',
                    $this->channel, $this->package
                )
            );
        }

        $packageInfo = $reg->getPackage($this->package, $this->channel);
        return $packageInfo;
    }

    /**
     * Generates the list of included files and directories
     *
     * @return boolean True if all went well, false if something was wrong
     *
     * @uses $filesIncluded
     * @uses $filesDeselected
     * @uses $filesNotIncluded
     * @uses $filesExcluded
     * @uses $everythingIncluded
     * @uses $dirsIncluded
     * @uses $dirsDeselected
     * @uses $dirsNotIncluded
     * @uses $dirsExcluded
     */
    public function scan()
    {
        $this->init();
        $list = $this->packageInfo->getFilelist();
        $found = null;
        foreach ($list as $file => $att) {
            if ($att['role'] != $this->role) {
                continue;
            }
            $this->filesIncluded[] = $file;
            $found = array($file, $att);
        }
        if ($found !== null) {
            list($file, $att) = $found;
            $this->setBaseDir(substr($att['installed_as'], 0, -strlen($file)));
        }

        return true;
    }

}
