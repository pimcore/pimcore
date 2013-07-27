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
 * Subversion Move command manager class
 *
 * Move and/or rename something in working copy or repository.
 *
 * Note: this subcommand is equivalent to a 'copy' and 'delete'.
 * 
 *   SRC and DST can both be working copy (WC) paths or URLs:
 *     WC  -> WC:   move and schedule for addition (with history)
 *     URL -> URL:  complete server-side rename.
 *
 * Copies must include a message option (either with 'm', 'message', 'file' or 'F'
 * keys), but the message itself may be blank.
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * $switches = array(
 *  'm [message]'   =>  'Specified commit message',
 *                      // either 'm' or 'message' may be used
 *  'F [file]'      =>  'Read commit message data from specified file',
 *                      // either 'F' or 'file' may be used
 *  'q [quiet]'     =>  true|false,
 *                      // prints as little as possible
 *  'force'         =>  true|false,
 *                      // force operation to run
 *  'r [revision]'  =>  'ARG (some commands also take ARG1:ARG2 range)
 *                        A revision argument can be one of:
 *                           NUMBER       revision number
 *                           "{" DATE "}" revision at start of the date
 *                           "HEAD"       latest in repository
 *                           "BASE"       base rev of item's working copy
 *                           "COMMITTED"  last commit at or before BASE
 *                           "PREV"       revision just before COMMITTED',
 *                      // either 'r' or 'revision' may be used
 *  'force-log'     =>  true|false,
 *                      // force validity of log message source
 *  'username'      =>  'Subversion repository login',
 *  'password'      =>  'Subversion repository password',
 *  'no-auth-cache' =>  true|false,
 *                      // Do not cache authentication tokens
 *  'encoding'      =>  'ARG',
 *                      // treat value as being in charset encoding ARG
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
 * The editor-cmd option available on the command-line svn client is not available
 * since this class does not operate as an interactive shell session.
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
 * $svn = VersionControl_SVN::factory(array('move'), $options);
 *
 * // Define any switches and aguments we may need
 * $switches = array('m' => 'Whoops! How did this get here?', 'username' => 'user', 'password' => 'pass');
 * $args = array(
 *      'svn://svn.example.com/repos/TestProj/trunk/somedir',           // SRC
 *      'svn://svn.example.com/repos/TestProj/trunk/lowerlevel/somedir' // DST
 * );
 *
 * // Run command
 * try {
 *     print_r($svn->move->run($args, $switches));
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
class VersionControl_SVN_Command_Move extends VersionControl_SVN_Command
{
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
                'm', 'message',
                'F', 'file',
                'editor-cmd',
                'encoding',
                'with-revprop'
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'q', 'quiet',
                'force',
                'parents',
                'force-log',
            )
        );
    }
}

?>
