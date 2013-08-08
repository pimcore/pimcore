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
 * Subversion Diff command manager class
 *
 * Display the differences between two paths.
 * 
 * From 'svn diff --help':
 *
 * usage: 1. diff [-r N[:M]] [--old OLD-TARGET] [--new NEW-TARGET] [PATH...]
 *        2. diff -r N:M URL
 *        3. diff [-r N[:M]] URL1[@N] URL2[@M]
 * 
 *   1. Display the differences between OLD-TARGET and NEW-TARGET.  PATHs, if
 *      given, are relative to OLD-TARGET and NEW-TARGET and restrict the output
 *      to differences for those paths.  OLD-TARGET and NEW-TARGET may be working
 *      copy paths or URL[@REV].
 * 
 *      OLD-TARGET defaults to the path '.' and NEW-TARGET defaults to OLD-TARGET.
 *      N defaults to BASE or, if OLD-TARGET is an URL, to HEAD.
 *      M defaults to the current working version or, if NEW-TARGET is an URL,
 *      to HEAD.
 * 
 *      '-r N' sets the revision of OLD-TGT to N, '-r N:M' also sets the
 *      revision of NEW-TGT to M.
 * 
 *   2. Shorthand for 'svn diff -r N:M --old=URL --new=URL'.
 * 
 *   3. Shorthand for 'svn diff [-r N[:M]] --old=URL1 --new=URL2'
 * 
 *   Use just 'svn diff' to display local modifications in a working copy
 *
 * Conversion of the above usage examples to VersionControl_SVN_Diff:
 *
 * Example 1:
 * <code>
 * <?php
 * require_once 'VersionControl/SVN.php';
 *
 * // Set up runtime options. Will be passed to all 
 * // subclasses.
 * $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW);
 *
 * $switches = array('r' => '5:8');
 * $args = array('svn://svn.example.com/repos/TestProj/trunk/example.php',
 *               'svn://svn.example.com/repos/TestProj/trunk/example2.php');
 * );
 *
 * $svn = VersionControl_SVN::factory(array('diff'), $options);
  * try {
 *     print_r($svn->diff->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 * ?>
 * </code>
 *
 * Example 2:
 * <code>
 * <?php
 * require_once 'VersionControl/SVN.php';
 *
 * // Set up runtime options. Will be passed to all 
 * // subclasses.
 * $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW);
 *
 * $switches = array('r' => '5:8');
 * $args = array('svn://svn.example.com/repos/TestProj/trunk/example.php');
 *
 * $svn = VersionControl_SVN::factory(array('diff'), $options);
  * try {
 *     print_r($svn->diff->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 * ?>
 * </code>
 *
 * Example 3:
 * <code>
 * <?php
 * require_once 'VersionControl/SVN.php';
 *
 * // Set up runtime options. Will be passed to all 
 * // subclasses.
 * $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW);
 *
 * $switches = array('r' => '5:8');
 * $args = array('svn://svn.example.com/repos/TestProj/trunk/example.php',
 *               'svn://svn.example.com/repos/TestProj/trunk/example2.php');
 *
 * $svn = VersionControl_SVN::factory(array('diff'), $options);
  * try {
 *     print_r($svn->diff->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 * ?>
 * </code>
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * $switches = array(
 *  'r [revision]'  =>  'ARG (some commands also take ARG1:ARG2 range)
 *                        A revision argument can be one of:
 *                           NUMBER       revision number
 *                           "{" DATE "}" revision at start of the date
 *                           "HEAD"       latest in repository
 *                           "BASE"       base rev of item's working copy
 *                           "COMMITTED"  last commit at or before BASE
 *                           "PREV"       revision just before COMMITTED',
 *                      // either 'r' or 'revision' may be used
 *  'old'           =>  'OLD-TARGET',
 *                      // use OLD-TARGET as the older target
 *  'new'           =>  'NEW-TARGET',
 *                      // use NEW-TARGET as the newer target
 *  'x [extensions]'=>  'ARG',
 *                      // pass ARG as bundled options to GNU diff
 *                      // either 'x' or 'extensions' may be used
 *  'N'             =>  true|false,
 *                      // operate on single directory only
 *  'non-recursive' =>  true|false,
 *                      // operate on single directory only
 *  'diff-cmd'      =>  'ARG',
 *                      // use ARG as diff command
 *  'no-diff-deleted' => true|false,
 *                      // do not print differences for deleted files
 *  'notice-ancestry' => true|false,
 *                      // notice ancestry when calculating differences
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
 * The editor-cmd option available on the command-line svn client is not available
 * since this class does not operate as an interactive shell session.
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  0.5.1
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Command_Diff extends VersionControl_SVN_Command
{
    /**
     * Minimum number of args required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var int $minArgs
     */
    public $minArgs = 1;

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
                'r', 'revision',
                'c', 'change',
                'old',
                'new',
                'depth',
                'diff-cmd',
                'x', 'extensions',
                'changelist',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'N', 'non-recursive',
                'no-diff-deleted',
                'notice-ancestry',
                'summarize',
                'force',
                'xml',
            )
        );
    }
}

?>
