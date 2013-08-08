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
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'VersionControl/SVN/Command.php';

/**
 * Subversion Status command manager class
 *
 * Print the status of working copy files and directories.
 *
 * There are many possible output values from this command. The following
 * is from 'svn help status':
 *
 *  With no args, print only locally modified items (no network access).
 *  With -u, add working revision and server out-of-date information.
 *  With -v, print full revision information on every item.
 *
 *  The first five columns in the output are each one character wide:
 *    First column: Says if item was added, deleted, or otherwise changed
 *      ' ' no modifications
 *      'A' Added
 *      'C' Conflicted
 *      'D' Deleted
 *      'G' Merged
 *      'I' Ignored
 *      'M' Modified
 *      'R' Replaced
 *      'X' item is unversioned, but is used by an externals definition
 *      '?' item is not under version control
 *      '!' item is missing (removed by non-svn command) or incomplete
 *      '~' versioned item obstructed by some item of a different kind
 *    Second column: Modifications of a file's or directory's properties
 *      ' ' no modifications
 *      'C' Conflicted
 *      'M' Modified
 *    Third column: Whether the working copy directory is locked
 *      ' ' not locked
 *      'L' locked
 *    Fourth column: Scheduled commit will contain addition-with-history
 *      ' ' no history scheduled with commit
 *      '+' history scheduled with commit
 *    Fifth column: Whether the item is switched relative to its parent
 *      ' ' normal
 *      'S' switched
 *
 *  The out-of-date information appears in the eighth column (with -u):
 *      '*' a newer revision exists on the server
 *      ' ' the working copy is up to date
 *
 *  Remaining fields are variable width and delimited by spaces:
 *    The working revision (with -u or -v)
 *    The last committed revision and last committed author (with -v)
 *    The working copy path is always the final field, so it can
 *      include spaces.
 *
 *  Example output:
 *    svn status wc
 *     M     wc/bar.c
 *    A  +   wc/qax.c
 *
 *    svn status -u wc
 *     M           965    wc/bar.c
 *           *     965    wc/foo.c
 *    A  +         965    wc/qax.c
 *    Head revision:   981
 *
 *    svn status --show-updates --verbose wc
 *     M           965       938 kfogel       wc/bar.c
 *           *     965       922 sussman      wc/foo.c
 *    A  +         965       687 joe          wc/qax.c
 *                 965       687 joe          wc/zig.c
 *    Head revision:   981
 *
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * $switches = array(
 *  'u'             =>  true|false,
 *                      // display update information
 *  'show-updates'  =>  true|false,
 *                      // display update information
 *  'v [verbose]'   =>  true|false,
 *                      // prints extra information
 *  'N'             =>  true|false,
 *                      // operate on a single directory only
 *  'non-recursive' =>  true|false,
 *                      // operate on a single directory only
 *  'q [quiet]'     =>  true|false,
 *                      // prints as little as possible
 *  'no-ignore'     =>  true|false,
 *                      // disregard default and svn:ignore property ignores
 *  'username'      =>  'Subversion repository login',
 *  'password'      =>  'Subversion repository password',
 *  'no-auth-cache' =>  true|false,
 *                      // Do not cache authentication tokens
 *  'config-dir'    =>  'Path to a Subversion configuration directory',
 *  'changelist     =>  'Changelist to operate on'
 * );
 *
 * </code>
 *
 * Note: Subversion does not offer an XML output option for this subcommand
 *
 * The non-interactive option available on the command-line 
 * svn client may also be set (true|false), but it is set to true by default.
 *
 * Usage example:
 * <code>
 * <?php
 * require_once 'VersionControl/SVN.php';
 *
 * // Set up runtime options. Will be passed to all 
 * // subclasses.
 * $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW);
 *
 * // Pass array of subcommands we need to factory
 * $svn = VersionControl_SVN::factory(array('status'), $options);
 *
 * // Define any switches and aguments we may need
 * $switches = array('u' => true, 'v' => true);
 * $args = array('/path/to/working/copy/TestProj/trunk');
 *
 * // Run command
 * try {
 *     print_r($svn->status->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 * ?>
 * </code>
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  0.5.1
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Command_Status extends VersionControl_SVN_Command
{
    /**
     * Keep track of whether XML output is available for a command
     *
     * @var boolean $xmlAvail
     */
    protected $xmlAvail = true;

    /**
     * Constuctor of command. Adds available switches.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validSwitchesValue = array_merge(
            $this->validSwitchesValue,
            array(
                'depth',
                'changelist',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'u', 'show-updates',
                'v', 'verbose',
                'N', 'non-recursive',
                'q', 'quiet',
                'no-ignore',
                'incremental',
                'xml',
                'ignore-externals',
            )
        );
    }
}

?>
