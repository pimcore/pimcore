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

require_once 'VersionControl/SVN/Command.php';

/**
 * Subversion Add command manager class
 *
 * Put files and directories under version control, scheduling
 * them for addition to repository.  They will be added in next commit.
 *
 * While it may or may not seem obvious, it's worth mentioning that 
 * the path to the file being added should already be an Subversion 
 * project working copy.
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * array(
 *  'config-dir'    =>  'Path to a Subversion configuration directory',
 *  'targets'       =>  'ARG',
 *                      // pass contents of file ARG as additional args
 *  'q [quiet]'     =>  true|false,
 *                     // prints as little as possible
 *  'v [verbose]'   =>  true|false,
 *                      // prints extra information
 *  'N'             =>  true|false,
 *                      // operate on single directory only
 *  'non-recursive' =>  true|false,
 *                      // operate on single directory only
 *  'auto-props'    =>  true|false,
 *                      // enable automatic properties. Setting to false IS NOT
 *                      // the same as setting no-auto-props to true.
 *  'no-auto-props' =>  true|false
 *                      // disable automatic properties. Setting to false IS NOT
 *                      // the same as setting auto-props to true.
 * );
 *
 * </code>
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
 * $svn = VersionControl_SVN::factory(array('add'), $options);
 *
 * // Define any switches and aguments we may need
 * $switches = array();
 * $args = array('/local_working_files/myproject/newfile.php');
 *
 * // Run command
 * try {
 *     print_r($svn->add->run($args, $switches));
 * } catch (VersionControl_SVN_Exception $e) {
 *     print_r($e->getMessage());
 * }
 * ?>
 * </code>
 *
 *
 * Note: Subversion does not offer an XML output option for this subcommand
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Michiel Rook <mrook@php.net>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  0.5.1
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Command_Add extends VersionControl_SVN_Command
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
                'targets',
                'depth',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'N', 'non-recursive',
                'q', 'quiet',
                'force',
                'no-ignore',
                'auto-props',
                'no-auto-props',
                'parents',
            )
        );
    }
}

?>
