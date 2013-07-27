<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * VersionControl_SVN_Info allows for XML formatted output. XML_Parser is used to
 * manipulate that output.
 *
 * +----------------------------------------------------------------------+
 * | This LICENSE is in the BSD license style.                            |
 * | http://www.opensource.org/licenses/bsd-license.php                   |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * |  * Redistributions of source code must retain the above copyright    |
 * |    notice, this list of conditions and the following disclaimer.     |
 * |                                                                      |
 * |  * Redistributions in binary form must reproduce the above           |
 * |    copyright notice, this list of conditions and the following       |
 * |    disclaimer in the documentation and/or other materials provided   |
 * |    with the distribution.                                            |
 * |                                                                      |
 * |  * Neither the name of Clay Loveless nor the names of contributors   |
 * |    may be used to endorse or promote products derived from this      |
 * |    software without specific prior written permission.               |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * PHP version 5
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'VersionControl/SVN/Exception.php';
require_once 'System.php';

/**
 * Ground class for a SVN command.
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   0.5.1
 * @link      http://pear.php.net/package/VersionControl_SVN
 */
abstract class VersionControl_SVN_Command
{
    /**
     * Indicates whether commands passed to the
     * {@link http://www.php.net/exec exec()} function in the
     * {@link run} method should be passed through
     * {@link http://www.php.net/escapeshellcmd escapeshellcmd()}.
     * NOTE: this variable is ignored on Windows machines!
     *
     * @var boolean $useEscapeshellcmd
     */
    public $useEscapeshellcmd = true;

    /**
     * Use exec or passthru to get results from command.
     *
     * @var bool $passthru
     */
    public $passthru = false;

    /**
     * Location of the svn client binary installed as part of Subversion
     *
     * @var string $binaryPath
     */
    public $binaryPath = '/usr/local/bin/svn';
    
    /**
     * Legacy / compatibility location of the svn client binary
     *
     * @var string $svn_path
     */
    public $svn_path = '';

    /**
     * String to prepend to command string. Helpful for setting exec() 
     * environment variables, such as: 
     *    export LANG=en_US.utf8 &&
     * ... to support non-ASCII file and directory names.
     * 
     * @var string $prependCmd
     */
    public $prependCmd = '';

    /**
     * Array of switches to use in building svn command
     *
     * @var array $switches
     */
    public $switches = array();

    /**
     * Switches required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var array $requiredSwitches
     */
    public $requiredSwitches = array();

    /**
     * Runtime options being used. 
     *
     * @var array $options
     */
    public $options = array();

    /**
     * Command-line arguments that should be passed
     * <b>outside</b> of those specified in {@link switches}.
     *
     * @var array $args
     */
    public $args = array();

    /**
     * Minimum number of args required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var int $minArgs
     */
    public $minArgs = 0;

    /**
     * Preferred fetchmode. Note that not all subcommands have output available for 
     * each preferred fetchmode. The default cascade is:
     *
     * VERSIONCONTROL_SVN_FETCHMODE_ASSOC
     *  VERSIONCONTROL_SVN_FETCHMODE_RAW
     *
     * If the specified fetchmode isn't available, raw output will be returned.
     * 
     * @var int $fetchmode
     */
    public $fetchmode = VERSIONCONTROL_SVN_FETCHMODE_ASSOC;

    /**
     * Default username to use for connections.
     *
     * @var string $username
     */
    public $username = '';

    /**
     * Default password to use for connections.
     *
     * @var string $password
     */
    public $password = '';

    /**
     * Default config-dir to use for connections.
     *
     * @var string $configDir
     */
    public $configDir = '';

    /**
     * Default config-option to use for connections.
     *
     * @var string $configOption
     */
    public $configOption = '';

    /**
     * SVN subcommand to run.
     * 
     * @var string $commandName
     */
    protected $commandName = '';

    /**
     * Fully prepared command string.
     * 
     * @var string $preparedCmd
     */
    protected $preparedCmd = '';

    /**
     * Keep track of whether XML output is available for a command
     *
     * @var boolean $xmlAvail
     */
    protected $xmlAvail = false;

    /**
     * Useable switches for command with parameters.
     */
    protected $validSwitchesValue = array(
        'username',
        'password',
        'config-dir',
        'config-option',
    );

    /**
     * Useable switches for command without parameters.
     */
    protected $validSwitches = array(
        'no-auth-cache',
        'non-interactive',
        'trust-server-cert',
    );

    /**
     * Constructor. Can't be called directly as class is abstract.
     */
    public function __construct()
    {
        $className = get_class($this);
        $this->commandName = strtolower(
            substr(
                $className,
                strrpos($className, '_') + 1
            )
        );
    }

    /**
     * Allow for overriding of previously declared options.     
     *
     * @param array $options An associative array of option names and
     *                       their values
     *
     * @return VersionControl_SVN_Command Themself.
     * @throws VersionControl_SVN_Exception If option isn't available.
     */
    public function setOptions($options = array())
    {
        $class = new ReflectionClass($this);

        foreach ($options as $option => $value) {
            try {
                $property = $class->getProperty($option);
            } catch (ReflectionException $e) {
                $property = null;
            }
            if (null !== $property && $property->isPublic()) {
                $this->$option = $value;
            } else {
                throw new VersionControl_SVN_Exception(
                    '"' . $option . '" is not a valid option',
                    VersionControl_SVN_Exception::INVALID_OPTION
                );
            }
        }

        return $this;
    }

    /**
     * Prepare the command switches.
     *
     * This function should be overloaded by the command class.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If preparing failed.
     */
    public function prepare()
    {
        $this->checkCommandRequirements();
        $this->preProcessSwitches();

        $invalidSwitches = array();
        $cmdParts = array(
            $this->binaryPath,
            $this->commandName
        );

        foreach ($this->switches as $switch => $val) {
            if (1 === strlen($switch)) {
                $switchPrefix = '-';
            } else {
                $switchPrefix = '--';
            }
            if (in_array($switch, $this->validSwitchesValue)) {
                $cmdParts[] = $switchPrefix . $switch . ' ' . escapeshellarg($val);
            } elseif (in_array($switch, $this->validSwitches)) {
                if (true === $val) {
                    $cmdParts[] = $switchPrefix . $switch;
                }
            } else {
                $invalidSwitches[] = $switch;
            }
        }

        $this->postProcessSwitches($invalidSwitches);

        $this->preparedCmd = implode(
            ' ', array_merge($cmdParts, $this->args)
        );
    }

    /**
     * Called after handling switches.
     *
     * @param array $invalidSwitches Invalid switches found while processing.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If switch(s) is/are invalid.
     */
    protected function postProcessSwitches($invalidSwitches)
    {
        $invalid = count($invalidSwitches);
        if ($invalid > 0) {
            $invalides = implode(',', $invalidSwitches);
            if ($invalid > 1) {
                $error = '"' . $invalides . '" are invalid switches';
            } else {
                $error = '"' . $invalides . '" is a invalid switch';
            }
            $error .= ' for class "' . get_class($this) . '".';
            throw new VersionControl_SVN_Exception(
                $error,
                VersionControl_SVN_Exception::INVALID_SWITCH
            );
        }
    }


    /**
     * Called before handling switches.
     *
     * @return void
     */
    protected function preProcessSwitches()
    {
        if ($this->xmlAvail
            && ($this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_ARRAY
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_ASSOC
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_OBJECT
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_XML)
        ) {
            $this->switches['xml'] = true;
        }
        $this->switches['non-interactive'] = true;

        $this->fillSwitch('username', $this->username);
        $this->fillSwitch('password', $this->password);
        $this->fillSwitch('config-dir', $this->configDir);
        $this->fillSwitch('config-option', $this->configOption);
    }

    protected function fillSwitch($switchName, $value)
    {
        if (!isset($this->switches[$switchName])
            && '' !== $value
        ) {
            $this->switches[$switchName] = $value;
        }
    }


    /**
     * Standardized validation of requirements for a command class.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If command requirements not resolved.
     */
    public function checkCommandRequirements()
    {
        // Set up error push parameters to avoid any notices about undefined indexes
        $params['options']     = $this->options;
        $params['switches']    = $this->switches;
        $params['args']        = $this->args;
        $params['commandName'] = $this->commandName;
        $params['cmd']         = '';
        
        // Check for minimum arguments
        if (sizeof($this->args) < $this->minArgs) {
            throw new VersionControl_SVN_Exception(
                'svn command requires at least ' . $this->minArgs . ' argument(s)',
                VersionControl_SVN_Exception::MIN_ARGS
            );
        }
        
        // Check for presence of required switches
        if (sizeof($this->requiredSwitches) > 0) {
            $missing    = array();
            $switches   = $this->switches;
            $reqsw      = $this->requiredSwitches;
            foreach ($reqsw as $req) {
                $found = false;
                $good_switches = explode('|', $req);
                foreach ($good_switches as $gsw) {
                    if (isset($switches[$gsw])) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $missing[] = '('.$req.')';
                }
            }
            $num_missing = count($missing);
            if ($num_missing > 0) {
                throw new VersionControl_SVN_Exception(
                    'svn command requires the following switch(es): ' . $missing,
                    VersionControl_SVN_Exception::SWITCH_MISSING
                );
            }
        }
    }

    /**
     * Run the command with the defined switches.
     *
     * @param array $args     Arguments to pass to Subversion
     * @param array $switches Switches to pass to Subversion
     *
     * @return  mixed   $fetchmode specified output on success.
     * @throws VersionControl_SVN_Exception If command failed.
     */
    public function run($args = array(), $switches = array())
    {
        if ($this->svn_path != '') {
            $this->binaryPath = $this->svn_path;
        }
        
        if (!file_exists($this->binaryPath)) {
            $system = new System();
            $this->binaryPath = $system->which('svn');
        }

        if (sizeof($switches) > 0) {
            $this->switches = $switches;
        }
        if (sizeof($args) > 0) {
            foreach (array_keys($args) as $k) {
                $this->args[$k] = escapeshellarg($args[$k]);
            }
        }

        // Always prepare, allows for obj re-use. (Request #5021)
        $this->prepare();

        $out       = array();
        // @var integer $returnVar Return number from shell execution.
        $returnVar = null;

        $cmd = $this->preparedCmd;

        // On Windows, don't use escapeshellcmd, and double-quote $cmd
        // so it's executed as 
        // cmd /c ""C:\Program Files\SVN\bin\svn.exe" info "C:\Program Files\dev\trunk""
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = str_replace(
                $this->binaryPath,
                escapeshellarg(str_replace('/', '\\', $this->binaryPath)),
                $cmd
            );

            if (!$this->passthru) {
                exec("cmd /c \"$cmd 2>&1\"", $out, $returnVar);
            } else {
                passthru("cmd /c \"$cmd 2>&1\"", $returnVar);
            }
        } else {
            if ($this->useEscapeshellcmd) {
                $cmd = escapeshellcmd($cmd);
            }
            if (!$this->passthru) {
                exec("{$this->prependCmd}$cmd 2>&1", $out, $returnVar);
            } else {
                passthru("{$this->prependCmd}$cmd 2>&1", $returnVar);
            }
        }

        if ($returnVar > 0) {
            throw new VersionControl_SVN_Exception(
                'Execution of command failed returning: ' . $returnVar
                . "\n" . implode("\n", $out),
                VersionControl_SVN_Exception::EXEC
            );
        }

        return $this->parseOutput($out);
    }

    /**
     * Handles output parsing of standard and verbose output of command.
     *
     * @param array $out Array of output captured by exec command in {@link run}
     *
     * @return  mixed   Returns output requested by fetchmode (if available), or 
     *                  raw output if desired fetchmode is not available.
     */
    public function parseOutput($out)
    {
        $dir = realpath(dirname(__FILE__)) . '/Parser/XML';
        switch($this->fetchmode) {
        case VERSIONCONTROL_SVN_FETCHMODE_ARRAY:
        case VERSIONCONTROL_SVN_FETCHMODE_ASSOC:
        case VERSIONCONTROL_SVN_FETCHMODE_OBJECT:
            $file = $dir . '/' . ucfirst($this->commandName) . '.php';
            if (file_exists($file)) {
                $class = 'VersionControl_SVN_Parser_XML_'
                    . ucfirst($this->commandName);

                include_once $file;
                $parser = new $class;
                $contentVar = $this->commandName;

                $parsedData = $parser->getParsed(join("\n", $out));
                if ($this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_OBJECT) {
                    return (object) $parsedData;
                }
                return $parsedData;
                break;
            }
        case VERSIONCONTROL_SVN_FETCHMODE_RAW:
        case VERSIONCONTROL_SVN_FETCHMODE_XML:
        default:
            // What you get with VERSIONCONTROL_SVN_FETCHMODE_DEFAULT
            return join("\n", $out);
            break;
        }
    }
}
?>
