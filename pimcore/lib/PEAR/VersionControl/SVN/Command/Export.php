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
 * Subversion Export command manager class
 *
 * Create an unversioned copy of a tree.
 * 
 * From 'svn export --help':
 *
 * usage: 1. export [-r REV] URL [PATH]
 *        2. export [-r REV] PATH1 [PATH2]
 * 
 *   1. Exports a clean directory tree from the repository specified by
 *      URL, at revision REV if it is given, otherwise at HEAD, into
 *      PATH. If PATH is omitted, the last component of the URL is used
 *      for the local directory name.
 * 
 *   2. Exports a clean directory tree from the working copy specified by
 *      PATH1, at revision REV if it is given, otherwise at WORKING, into
 *      PATH2.  If PATH2 is omitted, the last component of the PATH1 is used
 *      for the local directory name. If REV is not specified, all local
 *      changes will be preserved, but files not under version control will
 *      not be copied.
 *
 * Conversion of the above usage examples to VersionControl_SVN_Export:
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
 * $switches = array('r' => 'HEAD');
 * $args = array('svn://svn.example.com/repos/TestProj/trunk',
 *               '/my/export/path/Test-Project-1.0');
 *
 * $svn = VersionControl_SVN::factory(array('export'), $options);
 * try {
 *     print_r($svn->export->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 *
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
 * $args = array('/repos/TestProj/trunk',
 *               '/my/export/path/Test-Project-1.0');
 *
 * $svn = VersionControl_SVN::factory(array('export'), $options);
 * try {
 *     print_r($svn->export->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 *
 * ?>
 * </code>
 *
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
 *  'q [quiet]'     =>  true|false,
 *                      // prints as little as possible
 *  'force'         =>  true|false,
 *                      // force operation to run
 *  'username'      =>  'Subversion repository login',
 *  'password'      =>  'Subversion repository password',
 *  'no-auth-cache' =>  true|false,
 *                      // Do not cache authentication tokens
 *  'config-dir'    =>  'Path to a Subversion configuration directory'
 * );
 *
 * </code>
 *
 * Note: Subversion does not offer an XML output option for this subcommand
 *
 * The non-interactive option available on the command-line 
 * svn client may also be set (true|false), but it is set to true by default.
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  0.5.1
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Command_Export extends VersionControl_SVN_Command
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
     * Constuctor of command. Adds available switches.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validSwitchesValue = array_merge(
            $this->validSwitchesValue,
            array(
                'r', 'revision',
                'depth',
                'native-eol',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'q', 'quiet',
                'N', 'non-recursive',
                'force',
                'ignore-externals',
            )
        );
    }
}

?>
