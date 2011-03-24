<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP Version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Anders Johannsen <anders@johannsen.com>                      |
// | Author: Dan Allen <dan@mojavelinux.com>
// +----------------------------------------------------------------------+

// $Id: Command.php,v 1.9 2007/04/20 21:08:48 cconstantine Exp $

// }}}
// {{{ includes

require_once 'PEAR.php';
require_once 'System.php';

// }}}
// {{{ constants

define('SYSTEM_COMMAND_OK',                 1);
define('SYSTEM_COMMAND_ERROR',             -1);
define('SYSTEM_COMMAND_NO_SHELL',          -2);
define('SYSTEM_COMMAND_INVALID_SHELL',     -3);
define('SYSTEM_COMMAND_TMPDIR_ERROR',      -4);
define('SYSTEM_COMMAND_INVALID_OPERATOR',  -5);
define('SYSTEM_COMMAND_INVALID_COMMAND',   -6);
define('SYSTEM_COMMAND_OPERATOR_PLACEMENT',-7);
define('SYSTEM_COMMAND_COMMAND_PLACEMENT', -8);
define('SYSTEM_COMMAND_NOHUP_MISSING',     -9);
define('SYSTEM_COMMAND_NO_OUTPUT',        -10);
define('SYSTEM_COMMAND_STDERR',           -11);
define('SYSTEM_COMMAND_NONZERO_EXIT',     -12);

// }}}

// {{{ class System_Command

/**
 * The System_Command:: class implements an abstraction for various ways 
 * of executing commands (directly using the backtick operator,
 * as a background task after the script has terminated using
 * register_shutdown_function() or as a detached process using nohup).
 *
 * @author  Anders Johannsen <anders@johannsen.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @version $Revision: 1.9 $
 */

// }}}
class System_Command {
    // {{{ properties

    /**
     * Array of settings used when creating the shell command
     *
     * @var array
     * @access private
     */
    var $options = array();

    /**
     * Array of available shells to use to execute the command
     *
     * @var array
     * @access private
     */
    var $shells = array();

    /**
     * Array of available control operators used between commands
     *
     * @var array
     * @access private
     */
    var $controlOperators = array();

    /**
     * The system command to be executed
     *
     * @var string
     * @access private
     */
    var $systemCommand = null;

    /**
     * Previously added part to the command string
     *
     * @var string
     * @access private
     */
    var $previousElement = null;

    /**
     * Directory for writing stderr output
     *
     * @var string
     * @access private
     */
    var $tmpDir = null;

    /**
     * To allow the pear error object to accumulate when building
     * the command, we use the command status to keep track when
     * a pear error is raised
     *
     * @var int
     * @access private
     */
    var $commandStatus = 0;
    
    /**
     * Hold initialization PEAR_Error
     *
     * @var object
     * @access private
     **/
    var $_initError = null;
        
    // }}}
    // {{{ constructor

    /**
     * Class constructor
     * 
     * Defines all necessary constants and sets defaults
     * 
     * @access public
     */
    function System_Command($in_shell = null)
    {
        // Defining constants
        $this->options = array(
            'SEQUENCE'   => true,
            'SHUTDOWN'   => false,
            'SHELL'      => $this->which($in_shell),
            'OUTPUT'     => true,
            'NOHUP'      => false,
            'BACKGROUND' => false,
            'STDERR'     => false
        );

        // prepare the available control operators
        $this->controlOperators = array(
            'PIPE'  => '|',
            'AND'   => '&&',
            'OR'    => '||',
            'GROUP' => ';',
            'LFIFO' => '<',
            'RFIFO' => '>',
        );
                
        // List of allowed/available shells
        $this->shells = array(
            'sh',
            'bash',
            'zsh',
            'tcsh',
            'csh',
            'ash',
            'sash',
            'esh',
            'ksh'
        );
                                   
        // Find the first available shell
        if (empty($this->options['SHELL'])) {
            foreach ($this->shells as $shell) {
                if ($this->options['SHELL'] = $this->which($shell)) {
                    break;
                }
            }

            // see if we still have no shell
            if (empty($this->options['SHELL'])) {
            	$this->_initError =& PEAR::raiseError(null, SYSTEM_COMMAND_NO_SHELL, null, E_USER_WARNING, null, 'System_Command_Error', true);
                return;
            }
        }

        // Caputre a temporary directory for capturing stderr from commands
        $this->tmpDir = System::tmpdir();
        if (!System::mkDir("-p {$this->tmpDir}")) {
            $this->_initError =& PEAR::raiseError(null, SYSTEM_COMMAND_TMPDIR_ERROR, null, E_USER_WARNING, null, 'System_Command_Error', true);
            return;
        }
    }
        
    // }}}
    // {{{ setOption()

    /**
     * Sets the value for an option. Each option should be set to true
     * or false; except the 'SHELL' option which should be a string
     * naming a shell. The options are:
     * 
     * 'SEQUENCE'   Allow a sequence command or not (right now this is always on);
     *
     * 'SHUTDOWN'   Execute commands via a shutdown function;
     *
     * 'SHELL'      Path to shell;
     *
     * 'OUTPUT'     Output stdout from process;
     *
     * 'NOHUP'      Use nohup to detach process;
     *
     * 'BACKGROUND' Run as a background process with &;
     *
     * 'STDERR'     Output on stderr will raise an error, even if
     *              the command's exit value is zero. The output from
     *              stderr can be retrieved using the getDebugInfo()
     *              method of the Pear_ERROR object returned by
     *              execute().;
     *
     * @param string $in_option is a case-sensitive string,
     *                          corresponding to the option
     *                          that should be changed
     * @param mixed $in_setting is the new value for the option
     * @access public
     * @return bool true if succes, else false
     */
    function setOption($in_option, $in_setting)
    {
    	if ($this->_initError) {
            return $this->_initError;
        }

        $option = strtoupper($in_option);

        if (!isset($this->options[$option])) {
            PEAR::raiseError(null, SYSTEM_COMMAND_ERROR, null, E_USER_NOTICE, null, 'System_Command_Error', true);
            return false;
        }
                
        switch ($option) {
            case 'OUTPUT':
            case 'SHUTDOWN':
            case 'SEQUENCE':
            case 'BACKGROUND':
            case 'STDERR':
                $this->options[$option] = !empty($in_setting);
                return true;
            break;
                
            case 'SHELL':
                if (($shell = $this->which($in_setting)) !== false) {
                    $this->options[$option] = $shell;
                    return true;
                } 
                else {
                    PEAR::raiseError(null, SYSTEM_COMMAND_NO_SHELL, null, E_USER_NOTICE, $in_setting, 'System_Command_Error', true);
                    return false;
                }
            break;
                        
            case 'NOHUP':
                if (empty($in_setting)) {
                    $this->options[$option] = false;
                } 
                else if ($location = $this->which('nohup')) {
                    $this->options[$option] = $location;
                } 
                else {
                    PEAR::raiseError(null, SYSTEM_COMMAND_NOHUP_MISSING, null, E_USER_NOTICE, null, 'System_Command_Error', true);
                    return false;
                }
            break;
        }
    }
    
    // }}}
    // {{{ pushCommand()

    /**
     * Used to push a command onto the running command to be executed
     *
     * @param  string $in_command binary to be run
     * @param  string $in_argument either an option or argument value, to be handled appropriately
     * @param  string $in_argument
     * @param  ...
     *
     * @access public
     * @return boolean true on success {or System_Command_Error Exception}
     */
    function pushCommand($in_command)
    {
    	if ($this->_initError) {
            return $this->_initError;
        }
        
        if (!is_null($this->previousElement) && !in_array($this->previousElement, $this->controlOperators)) {
            $this->commandStatus = -1;
            $error = PEAR::raiseError(null, SYSTEM_COMMAND_COMMAND_PLACEMENT, null, E_USER_WARNING, null, 'System_Command_Error', true);
        }

        // check for error here
        $command = escapeshellcmd($this->which($in_command));
        if ($command === false) {
            $error = PEAR::raiseError(null, SYSTEM_COMMAND_INVALID_COMMAND, null, E_USER_WARNING, null, 'System_Command_Error', true);
        }

        $argv = func_get_args();
        array_shift($argv);
        foreach($argv as $arg) {
            if (strpos($arg, '-') === 0) {
                $command .= ' ' . $arg; 
            }
            elseif ($arg != '') {
                $command .= ' ' . escapeshellarg($arg);
            }
        }

        $this->previousElement = $command;
        $this->systemCommand .= $command;

        return isset($error) ? $error : true;
    }

    // }}}
    // {{{ pushOperator()

    /**
     * Used to push an operator onto the running command to be executed
     *
     * @param  string $in_operator Either string reprentation of operator or system character
     *
     * @access public
     * @return boolean true on success {or System_Command_Error Exception}
     */
    function pushOperator($in_operator)
    {
    	if ($this->_initError) {
            return $this->_initError;
        }

        $operator = isset($this->controlOperators[$in_operator]) ? $this->controlOperators[$in_operator] : $in_operator;

        if (is_null($this->previousElement) || in_array($this->previousElement, $this->controlOperators)) {
            $this->commandStatus = -1;
            $error = PEAR::raiseError(null, SYSTEM_COMMAND_OPERATOR_PLACEMENT, null, E_USER_WARNING, null, 'System_Command_Error', true);
        }
        elseif (!in_array($operator, $this->controlOperators)) {
            $this->commandStatus = -1;
            $error = PEAR::raiseError(null, SYSTEM_COMMAND_INVALID_OPERATOR, null, E_USER_WARNING, $operator, 'System_Command_Error', true);
        }

        $this->previousElement = $operator;
        $this->systemCommand .= ' ' . $operator . ' ';
        return isset($error) ? $error : true;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes the code according to given options
     *
     * @return bool true if success {or System_Command_Exception}
     *
     * @access public
     */
    function execute() 
    {
    	if ($this->_initError) {
            return $this->_initError;
        }

        // if the command is empty or if the last element was a control operator, we can't continue
        if (is_null($this->previousElement) || $this->commandStatus == -1 || in_array($this->previousElement, $this->controlOperators)) {
            return PEAR::raiseError(null, SYSTEM_COMMAND_INVALID_COMMAND, null, E_USER_WARNING, $this->systemCommand, 'System_Command_Error', true);
        }

        // Warning about impossible mix of options
        if (!empty($this->options['OUTPUT'])) {
            if (!empty($this->options['SHUTDOWN']) || !empty($this->options['NOHUP'])) {
                return PEAR::raiseError(null, SYSTEM_COMMAND_NO_OUTPUT, null, E_USER_WARNING, null, 'System_Command_Error', true);
            }
        }
                
        // if this is not going to stdout, then redirect to /dev/null
        if (empty($this->options['OUTPUT'])) {
            $this->systemCommand .= ' >/dev/null';
        }
                
        $suffix = '';
        // run a command immune to hangups, with output to a non-tty
        if (!empty($this->options['NOHUP'])) {
            $this->systemCommand = $this->options['NOHUP'] . $this->systemCommand;
        }
        // run a background process (only if not nohup)
        elseif (!empty($this->options['BACKGROUND'])) {
            $suffix = ' &';
        }
                
        // Register to be run on shutdown
        if (!empty($this->options['SHUTDOWN'])) {
            $line = "system(\"{$this->systemCommand}$suffix\");";
            $function = create_function('', $line);
            register_shutdown_function($function);
            return true;
        } 
        else {
            // send stderr to a file so that we can reap the error message
            $tmpFile = tempnam($this->tmpDir, 'System_Command-');
            $this->systemCommand .= ' 2>' . $tmpFile . $suffix;
            $shellPipe = $this->which('echo') . ' ' . escapeshellarg($this->systemCommand) . ' | ' . $this->options['SHELL'];
            exec($shellPipe, $result, $returnVal);

            if ($returnVal !== 0) {
                // command returned nonzero; that's always an error
                $return = PEAR::raiseError(null, SYSTEM_COMMAND_NONZERO_EXIT, null, E_USER_WARNING, null, 'System_Command_Error', true);
            }
            else if (!$this->options['STDERR']) {
                // caller does not care about stderr; return success
                $return = implode("\n", $result);
            }
            else {
                // our caller cares about stderr; check stderr output
                clearstatcache();
                if (filesize($tmpFile) > 0) {
                    // the command actually wrote to stderr
                    $stderr_output = file_get_contents($tmpFile);
                    $return = PEAR::raiseError(null, SYSTEM_COMMAND_STDERR, null, E_USER_WARNING, $stderr_output, 'System_Command_Error', true);
                } else {
                    // total success; return stdout gathered by exec()
                    $return = implode("\n", $result);
                }
            }

            unlink($tmpFile);
            return $return;
        }
    }

    // }}}
    // {{{ which()

    /**
     * Functionality similiar to unix 'which'. Searches the path
     * for the specified program. 
     *
     * @param $cmd name of the executable to search for 
     *
     * @access private
     * @return string returns the full path if found, false if not
     */
    function which($in_cmd)
    {
        // only pass non-empty strings to System::which()
        if (!is_string($in_cmd) || '' === $in_cmd) {
            return(false);
        }

        // explicitly pass false as fallback value
        return System::which($in_cmd, false);
    }   

    // }}}
    // {{{ reset()

    /**
     * Prepare for a new command to be built
     *
     * @access public
     * @return void
     */
    function reset()
    {
        $this->previousElement = null;
        $this->systemCommand = null;
        $this->commandStatus = 0;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for a System_Command error code
     *
     * @param integer error code
     *
     * @return string error message, or false if the error code was
     * not recognized
     */
    function errorMessage($in_value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                SYSTEM_COMMAND_OK                     => 'no error',
                SYSTEM_COMMAND_ERROR                  => 'unknown error',
                SYSTEM_COMMAND_NO_SHELL               => 'no shell found',
                SYSTEM_COMMAND_INVALID_SHELL          => 'invalid shell',
                SYSTEM_COMMAND_TMPDIR_ERROR           => 'could not create temporary directory',
                SYSTEM_COMMAND_INVALID_OPERATOR       => 'control operator invalid',
                SYSTEM_COMMAND_INVALID_COMMAND        => 'invalid system command',
                SYSTEM_COMMAND_OPERATOR_PLACEMENT     => 'invalid placement of control operator',
                SYSTEM_COMMAND_COMMAND_PLACEMENT      => 'invalid placement of command',
                SYSTEM_COMMAND_NOHUP_MISSING          => 'nohup not found on system',
                SYSTEM_COMMAND_NO_OUTPUT              => 'output not allowed',
                SYSTEM_COMMAND_STDERR                 => 'command wrote to stderr',
                SYSTEM_COMMAND_NONZERO_EXIT           => 'non-zero exit value from command',
            );
        }

        if (System_Command::isError($in_value)) {
            $in_value = $in_value->getCode();
        }

        return isset($errorMessages[$in_value]) ? $errorMessages[$in_value] : $errorMessages[SYSTEM_COMMAND_ERROR];
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a System_Command method is an error
     *
     * @param int result code
     *
     * @return bool whether $in_value is an error
     *
     * @access public
     */
    function isError($in_value)
    {
        return (is_object($in_value) &&
                (strtolower(get_class($in_value)) == 'system_command_error' ||
                 is_subclass_of($in_value, 'system_command_error')));
    }
    
    // }}}
}

// {{{ class System_Command_Error

/**
 * System_Command_Error constructor.
 *
 * @param mixed      System_Command error code, or string with error message.
 * @param integer    what "error mode" to operate in
 * @param integer    what error level to use for $mode & PEAR_ERROR_TRIGGER
 * @param mixed      additional debug info, such as the last query
 *
 * @access public
 *
 * @see PEAR_Error
 */

// }}}
class System_Command_Error extends PEAR_Error
{
    // {{{ properties

    /**
     * Message in front of the error message
     * @var string $error_message_prefix
     */
    var $error_message_prefix = 'System_Command Error: ';

    // }}}
    // {{{ constructor

    function System_Command_Error($code = SYSTEM_COMMAND_ERROR, $mode = PEAR_ERROR_RETURN,
              $level = E_USER_NOTICE, $debuginfo = null)
    {
        if (is_int($code)) {
            $this->PEAR_Error(System_Command::errorMessage($code), $code, $mode, $level, $debuginfo);
        } else {
            $this->PEAR_Error("Invalid error code: $code", SYSTEM_COMMAND_ERROR, $mode, $level, $debuginfo);
        }
    }
    
    // }}}
}
?>
