<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
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

/**
 * Note on the fetch modes -- as the project matures, more of these modes
 * will be implemented. At the time of initial release only the 
 * Log and List commands implement anything other than basic
 * RAW output.
 */

/**
 * This is a special constant that tells VersionControl_SVN the user hasn't specified
 * any particular get mode, so the default should be used.
 */
define('VERSIONCONTROL_SVN_FETCHMODE_DEFAULT', 0);

/**
 * Responses returned in associative array format
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ASSOC', 1);

/**
 * Responses returned as object properties
 */
define('VERSIONCONTROL_SVN_FETCHMODE_OBJECT', 2);

/**
 * Responses returned as raw XML (as passed-thru from svn --xml command responses)
 */
define('VERSIONCONTROL_SVN_FETCHMODE_XML', 3);

/**
 * Responses returned as string - unmodified from command-line output
 */
define('VERSIONCONTROL_SVN_FETCHMODE_RAW', 4);

/**
 * Responses returned as raw output, but all available output parsing methods
 * are performed and stored in the {@link output} property.
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ALL', 5);

/**
 * Responses returned as numbered array
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ARRAY', 6);

/**
 * Simple OO interface for Subversion 
 *
 * @tutorial  VersionControl_SVN.pkg
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.killersoft.com/LICENSE.txt BSD License
 * @version   0.5.1
 * @link      http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN
{
    /**
     * Reference array of subcommand shortcuts. Provided for convenience for 
     * those who prefer the shortcuts they're used to using with the svn
     * command-line tools.
     *
     * You may specify your own shortcuts by passing them in to the factory.
     * For example:
     *
     * <code>
     * <?php
     * require_once 'VersionControl/SVN.php';
     *
     * $options['shortcuts'] = array('boot' => 'Delete', 'checkin' => 'Commit');
     *
     * $svn = VersionControl_SVN::factory(array('boot', 'checkin'), $options);
     *
     * $switches = array(
     *                 'username' => 'user', 'password' => 'pass', 'force' => true
     *             );
     * $args = array('svn://svn.example.com/repos/TestProject/file_to_delete.txt');
     *
     * $svn->boot->run($switches, $args);
     *
     * ?>
     * </code>
     *
     * @var array $shortcuts Possible shortcuts and their real commands.
     */
    public static $shortcuts = array(
        'praise'    => 'Blame',
        'annotate'  => 'Blame',
        'ann'       => 'Blame',
        'co'        => 'Checkout',
        'ci'        => 'Commit',
        'cp'        => 'Copy',
        'del'       => 'Delete',
        'remove'    => 'Delete',
        'rm'        => 'Delete',
        'di'        => 'Diff',
        'ls'        => 'List',
        'mv'        => 'Move',
        'rename'    => 'Move',
        'ren'       => 'Move',
        'pdel'      => 'Propdel',
        'pd'        => 'Propdel',
        'pget'      => 'Propget',
        'pg'        => 'Propget',
        'plist'     => 'Proplist',
        'pl'        => 'Proplist',
        'pset'      => 'Propset',
        'ps'        => 'Propset',
        'stat'      => 'Status',
        'st'        => 'Status',
        'sw'        => 'Switch',
        'up'        => 'Update'
    );

    /**
     * Create a new VersionControl_SVN command object.
     *
     * $options is an array containing multiple options
     * defined by the following associative keys:
     *
     * <code>
     *
     * array(
     *  'username'      => 'Subversion repository login',
     *  'password'      => 'Subversion repository password',
     *  'config-dir'    => 'Path to a Subversion configuration directory',
     *                     // [DEFAULT: null]
     *  'config-option' => 'Set Subversion user configuration',
     *                     // [DEFAULT: null]
     *  'binaryPath'    => 'Path to the svn client binary installed as part of Subversion',
     *                     // [DEFAULT: /usr/local/bin/svn]
     *  'fetchmode'     => Type of returning of run function.
     *                     // [DEFAULT: VERSIONCONTROL_SVN_FETCHMODE_ASSOC]
     * )
     *
     * </code>
     *
     * Example 1.
     * <code>
     * <?php
     * require_once 'VersionControl/SVN.php';
     *
     * $options = array(
     *      'username'   => 'your_login',
     *      'password'   => 'your_password',
     * );
     * 
     * // Run a log command
     * $svn = VersionControl_SVN::factory('log', $options);
     *
     * print_r($svn->run(array('path_to_your_svn'));
     * ?>
     * </code>
     *
     * @param string $command The Subversion command
     * @param array  $options An associative array of option names and
     *                        their values
     *
     * @return mixed A newly created command object or an stdObj with the
     *               command objects set.
     * @throws VersionControl_SVN_Exception Exception if command init fails.
     */
    public static function factory($command, $options = array())
    {
        if (is_string($command) && strtoupper($command) == '__ALL__') {
            unset($command);
            $command = array();
            $command = VersionControl_SVN::fetchCommands();
        }
        if (is_array($command)) {
            $objects = new stdClass;
            foreach ($command as $cmd) {
                $obj = VersionControl_SVN::init($cmd, $options);
                $objects->$cmd = $obj;
            }
            return $objects;
        } else {
            $obj = VersionControl_SVN::init($command, $options);
            return $obj;
        }
    }

    /**
     * Initialize an object wrapper for a Subversion subcommand.
     *
     * @param string $command The Subversion command
     * @param array  $options An associative array of option names and
     *                        their values
     *
     * @return VersionControl_SVN_Command Instance of command.
     * @throws VersionControl_SVN_Exception Exception if command init fails.
     */
    public static function init($command, $options)
    {
        // Check for shortcuts for commands
        $shortcuts = self::$shortcuts;
        
        if (isset($options['shortcuts']) && is_array($options['shortcuts'])) {
            foreach ($options['shortcuts'] as $key => $val) {
                $shortcuts[strtolower($key)] = $val;       
            }
        }
        
        $cmd   = isset($shortcuts[strtolower($command)])
            ? $shortcuts[strtolower($command)]
            : $command;
        $cmd   = ucfirst(strtolower($cmd));
        $class = 'VersionControl_SVN_Command_' . $cmd;
        
        if (include_once realpath(dirname(__FILE__)) . "/SVN/Command/{$cmd}.php") {
            if (class_exists($class)) {
                $obj = new $class;
                $obj->options = $options;
                $obj->setOptions($options);
                return $obj;
            }
        }

        throw new VersionControl_SVN_Exception(
            $command . ' is not a known VersionControl_SVN command.',
            VersionControl_SVN_Exception::UNKNOWN_CMD
        );
    }
    
    /**
     * Scan through the SVN directory looking for subclasses.
     *
     * @return array Array with names of commands as value.
     * @throws VersionControl_SVN_Exception Exception if fetching commands fails.
     */
    public static function fetchCommands()
    {
        $commands = array();
        $dir = realpath(dirname(__FILE__)) . '/SVN/Command';
        if (false === $dir
            || !is_dir($dir)
            || !is_readable($dir)
        ) {
            throw new VersionControl_SVN_Exception(
                'The path /SVN/Command doesn\'t exists or isn\'t readable.',
                VersionControl_SVN_Exception::ERROR
            );
        }
        $dirEntries = glob($dir . '/*.php');
        foreach ($dirEntries as $entry) {
            if (is_file($entry)
                && is_readable($entry)
            ) {
                $commands[] = strtolower(basename($entry, '.php'));
            }
        }

        return $commands;
    }

    /**
     * Return the VersionControl_SVN API version
     *
     * @return string The VersionControl_SVN API version number.
     */
    public static function apiVersion()
    {
        return '0.5.1';
    }
}
?>
