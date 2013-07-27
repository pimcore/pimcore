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
 * Subversion Propset command manager class
 *
 * Set PROPNAME to PROPVAL on files, dirs, or revisions.
 *
 * Note: svn recognizes the following special versioned properties
 * but will store any arbitrary properties set:
 *   svn:ignore     - A newline separated list of file patterns to ignore.
 *   svn:keywords   - Keywords to be expanded.  Valid keywords are:
 *     URL, HeadURL             - The URL for the head version of the object.
 *     Author, LastChangedBy    - The last person to modify the file.
 *     Date, LastChangedDate    - The date/time the object was last modified.
 *     Rev, LastChangedRevision - The last revision the object changed.
 *     Id                       - A compressed summary of the previous
 *                                  4 keywords.
 *   svn:executable - If present, make the file executable. This
 *     property cannot be set on a directory.  A non-recursive attempt
 *     will fail, and a recursive attempt will set the property only
 *     on the file children of the directory.
 *   svn:eol-style  - One of 'native', 'LF', 'CR', 'CRLF'.
 *   svn:mime-type  - The mimetype of the file.  Used to determine
 *     whether to merge the file, and how to serve it from Apache.
 *     A mimetype beginning with 'text/' (or an absent mimetype) is
 *     treated as text.  Anything else is treated as binary.
 *   svn:externals  - A newline separated list of module specifiers,
 *     each of which consists of a relative directory path, optional
 *     revision flags, and an URL.  For example
 *       foo             http://example.com/repos/zig
 *       foo/bar -r 1234 http://example.com/repos/zag
 *
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * $switches = array(
 *  'F [file]'      =>  'Read data from specified file',
 *                      // either 'F' or 'file' may be used
 *  'q [quiet]'     =>  true|false,
 *                      // print as little as possible
 *  'R'             =>  true|false,
 *                      // descend recursively
 *  'recursive'     =>  true|false,
 *                      // descend recursively
 *  'revprop'       =>  true|false,
 *                      // operate on a revision property (use with r)
 *  'targets'       =>  'ARG',
 *                      // pass contents of file ARG as additional args
 *  'r [revision]'  =>  'ARG (some commands also take ARG1:ARG2 range)
 *                        A revision argument can be one of:
 *                           NUMBER       revision number
 *                           "{" DATE "}" revision at start of the date
 *                           "HEAD"       latest in repository
 *                           "BASE"       base rev of item's working copy
 *                           "COMMITTED"  last commit at or before BASE
 *                           "PREV"       revision just before COMMITTED',
 *                      // either 'r' or 'revision' may be used
 *  'username'      =>  'Subversion repository login',
 *  'password'      =>  'Subversion repository password',
 *  'no-auth-cache' =>  true|false,
 *                      // Do not cache authentication tokens
 *  'encoding'      =>  'ARG',
 *                      // treat value as being in charset encoding ARG
 *  'force'         =>  true|false,
 *                      // force operation to run
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
 * $svn = VersionControl_SVN::factory(array('propset'), $options);
 *
 * // Define any switches and aguments we may need
 * $switches = array('username' => 'user', 'password' => 'pass');
 * $args = array('svn:mime-type', 'image/jpeg', 'foo.jpg');
 *
 * // Run command
 * try {
 *     print_r($svn->propset->run($args, $switches));
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
class VersionControl_SVN_Command_Propset extends VersionControl_SVN_Command
{
    /**
     * Minimum number of args required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var int $minArgs
     */
    public $minArgs = 3;

    /**
     * Constuctor of command. Adds available switches.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validSwitchesValue = array_merge(
            $this->validSwitchesValue,
            array(
                'F', 'file',
                'encoding',
                'r', 'revision',
                'targets',
                'depth',
                'changelist',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'q', 'quiet',
                'R', 'recursive',
                'revprop',
                'force',
            )
        );
    }
}

?>
