<?php
/**
 * $Id: b72d02e8f0d73e9a0533c249bcee161beeca5fb0 $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/Task.php';
require_once 'phing/tasks/ext/ioncube/IoncubeComment.php';

/**
 * Invokes the ionCube Encoder (PHP4 or PHP5)
 *
 * @author Michiel Rook <mrook@php.net>
 * @author Andrew Eddie <andrew.eddie@jamboworks.com> 
 * @author Domenico Sgarbossa <sbraaaa@yahoo.it> 
 * @version $Id: b72d02e8f0d73e9a0533c249bcee161beeca5fb0 $
 * @package phing.tasks.ext.ioncube
 * @since 2.2.0
 */
class IoncubeEncoderTask extends Task
{
    private $ionSwitches = array();
    
    private $ionOptions = array();
    
    private $ionOptionsXS = array();

    private $comments = array();
    
    private $encoderName = 'ioncube_encoder';
    
    private $fromDir = '';

    private $ioncubePath = '/usr/local/ioncube';

    private $phpVersion = '5';

    private $targetOption = '';

    private $toDir = '';

    private $showCommandLine = false;
 
    /**
     * Sets whether to show command line before it is executed
     */
    function setShowCommandLine($value)
    {
        $this->showCommandLine = $value;
    }

    /**
     * Adds a comment to be used in encoded files
     */
    function addComment(IoncubeComment $comment)
    {
        $this->comments[] = $comment;
    }

    /**
     * Sets the allowed server
     */
    function setAllowedServer($value)
    {
        $this->ionOptionsXS['allowed-server'] = $value;
    }

    /**
     * Returns the allowed server setting
     */
    function getAllowedServer()
    {
        return $this->ionOptionsXS['allowed-server'];
    }

    /**
     * Sets the binary option
     */
    function setBinary($value)
    {
        $this->ionSwitches['binary'] = $value;
    }

    /**
     * Returns the binary option
     */
    function getBinary()
    {
        return $this->ionSwitches['binary'];
    }

    /**
     * Sets files or folders to copy (separated by space)
     */
    function setCopy($value)
    {
        $this->ionOptionsXS['copy'] = $value;
    }

    /**
     * Returns the copy setting
     */
    function getCopy()
    {
        return $this->ionOptionsXS['copy'];
    }

    /**
     * Sets additional file patterns, files or directories to encode,
     * or to reverse the effect of copy (separated by space)
     */
    function setEncode($value)
    {
        $this->ionOptionsXS['encode'] = $value;
    }
    
    /**
     * Returns the encode setting
     */
    function getEncode()
    {
        return $this->ionOptionsXS['encode'];
    }

    /**
     * Sets regexps of additional files to encrypt (separated by space)
     */
    function setEncrypt($value)
    {
        $this->ionOptionsXS['encrypt'] = $value;
    }
    
    /**
     * Returns regexps of additional files to encrypt (separated by space)
     */
    function getEncrypt()
    {
        return $this->ionOptionsXS['encrypt'];
    }

    /**
     * Sets a period after which the files expire
     */
    function setExpirein($value)
    {
        $this->ionOptions['expire-in'] = $value;
    }
    
    /**
     * Returns the expireIn setting
     */
    function getExpirein()
    {
        return $this->ionOptions['expire-in'];
    }

    /**
     * Sets a YYYY-MM-DD date to expire the files 
     */
    function setExpireon($value)
    {
        $this->ionOptions['expire-on'] = $value;
    }
    
    /**
     * Returns the expireOn setting
     */
    function getExpireon()
    {
        return $this->ionOptions['expire-on'];
    }

    /**
     * Sets the source directory
     */
    function setFromDir($value)
    {
        $this->fromDir = $value;
    }

    /**
     * Returns the source directory
     */
    function getFromDir()
    {
        return $this->fromDir;
    }

    /**
     * Set files and directories to ignore entirely and exclude from the target directory
     * (separated by space).
     */
    function setIgnore($value)
    {
        $this->ionOptionsXS['ignore'] = $value;
    }

    /**
     * Returns the ignore setting
     */
    function getIgnore()
    {
        return $this->ionOptionsXS['ignore'];
    }

    /**
     * Sets the path to the ionCube encoder
     */
    function setIoncubePath($value)
    {
        $this->ioncubePath = $value;
    }

    /**
     * Returns the path to the ionCube encoder
     */
    function getIoncubePath()
    {
        return $this->ioncubePath;
    }

    /**
     * Set files and directories not to be ignored (separated by space).
     */
    function setKeep($value)
    {
        $this->ionOptionsXS['keep'] = $value;
    }

    /**
     * Returns the ignore setting
     */
    function getKeep()
    {
        return $this->ionOptionsXS['keep'];
    }

    /**
     * Sets the path to the license file to use
     */
    function setLicensePath($value)
    {
        $this->ionOptions['with-license'] = $value;
    }

    /**
     * Returns the path to the license file to use
     */
    function getLicensePath()
    {
        return $this->ionOptions['with-license'];
    }

    /**
     * Sets the no-doc-comments option
     */
    function setNoDocComments($value)
    {
        $this->ionSwitches['no-doc-comment'] = $value;
    }
    
    /**
     * Returns the no-doc-comments option
     */
    function getNoDocComments()
    {
        return $this->ionSwitches['no-doc-comment'];
    }
    
    /**
     * Sets the obfuscate option
     */
    function setObfuscate($value)
    {
        $this->ionOptionsXS['obfuscate'] = $value;
    }
    
    /**
     * Returns the optimize option
     */
    function getObfuscate()
    {
        return $this->ionOptionsXS['obfuscate'];
    }

    /**
     * Sets the obfuscation key (required if using the obfuscate option)
     */
    function setObfuscationKey($value)
    {
        $this->ionOptions['obfuscation-key'] = $value;
    }
    
    /**
     * Returns the optimize option
     */
    function getObfuscationKey()
    {
        return $this->ionOptions['obfuscation-key'];
    }

    /**
     * Sets the optimize option
     */
    function setOptimize($value)
    {
        $this->ionOptions['optimize'] = $value;
    }
    
    /**
     * Returns the optimize option
     */
    function getOptimize()
    {
        return $this->ionOptions['optimize'];
    }

    /**
     * Sets the passphrase to use when encoding files
     */
    function setPassPhrase($value)
    {
        $this->ionOptions['passphrase'] = $value;
    }

    /**
     * Returns the passphrase to use when encoding files
     */
    function getPassPhrase()
    {
        return $this->ionOptions['passphrase'];
    }

    /**
     * Sets the version of PHP to use (defaults to 5)
     */
    function setPhpVersion($value)
    {
        $this->phpVersion = $value;
    }

    /**
     * Returns the version of PHP to use (defaults to 5)
     */
    function getPhpVersion()
    {
        return $this->phpVersion;
    }
    
    /**
     * Sets the target directory
     */
    function setToDir($value)
    {
        $this->toDir = $value;
    }

    /**
     * Returns the target directory
     */
    function getToDir()
    {
        return $this->toDir;
    }

    /**
     * Sets the without-runtime-loader-support option
     */
    function setWithoutRuntimeLoaderSupport($value)
    {
        $this->ionSwitches['without-runtime-loader-support'] = $value;
    }
    
    /**
     * Returns the without-runtime-loader-support option
     */
    function getWithoutRuntimeLoaderSupport()
    {
        return $this->ionSwitches['without-runtime-loader-support'];
    }

    /**
     * Sets the no-short-open-tags option
     */
    function setNoShortOpenTags($value)
    {
        $this->ionSwitches['no-short-open-tags'] = $value;
    }
    
    /**
     * Returns the no-short-open-tags option
     */
    function getNoShortOpenTags()
    {
        return $this->ionSwitches['no-short-open-tags'];
    }

    /**
     * Sets the ignore-deprecated-warnings option
     */
    function setIgnoreDeprecatedWarnings($value)
    {
        $this->ionSwitches['ignore-deprecated-warnings'] = $value;
    }

    /**
     * Returns the ignore-deprecated-warnings option
     */
    function getIgnoreDeprecatedWarnings()
    {
        return $this->ionSwitches['ignore-deprecated-warnings'];
    }

    /**
     * Sets the ignore-strict-warnings option
     */
    function setIgnoreStrictWarnings($value)
    {
        $this->ionSwitches['ignore-strict-warnings'] = $value;
    }

    /**
     * Returns the ignore-strict-warnings option
     */
    function getIgnoreStrictWarnings()
    {
        return $this->ionSwitches['ignore-strict-warnings'];
    }

    /**
     * Sets the allow-encoding-into-source option
     */
    function setAllowEncodingIntoSource($value)
    {
        $this->ionSwitches['allow-encoding-into-source'] = $value;
    }

    /**
     * Returns the allow-encoding-into-source option
     */
    function getAllowEncodingIntoSource()
    {
        return $this->ionSwitches['allow-encoding-into-source'];
    }

    /**
     * Sets the message-if-no-loader option
     */
    function setMessageIfNoLoader($value)
    {
        $this->ionOptions['message-if-no-loader'] = $value;
    }

    /**
     * Returns the message-if-no-loader option
     */
    function getMessageIfNoLoader()
    {
        return $this->ionOptions['message-if-no-loader'];
    }

    /**
     * Sets the action-if-no-loader option
     */
    function setActionIfNoLoader($value)
    {
        $this->ionOptions['action-if-no-loader'] = $value;
    }

    /**
     * Returns the action-if-no-loader option
     */
    function getActionIfNoLoader()
    {
        return $this->ionOptions['action-if-no-loader'];
    }

    /**
     * Sets the option to use when encoding target directory already exists (defaults to none)
     */
    function setTargetOption($targetOption)
    {
        $this->targetOption = $targetOption;
    }

    /**
     * Returns he option to use when encoding target directory already exists (defaults to none)
     */
    function getTargetOption()
    {
        return $this->targetOption;
    }
    
    /**
     * Sets the callback-file option
     */
    function setCallbackFile($value)
    {
        $this->ionOptions['callback-file'] = $value;
    }

    /**
     * Returns the callback-file option
     */
    function getCallbackFile()
    {
        return $this->ionOptions['callback-file'];
    }

    /**
     * Sets the obfuscation-exclusions-file option
     */
    function setObfuscationExclusionFile($value)
    {
        $this->ionOptions['obfuscation-exclusion-file'] = $value;
    }

    /**
     * Returns the obfuscation-exclusions-file option
     */
    function getObfuscationExclusionFile()
    {
        return $this->ionOptions['obfuscation-exclusion-file'];
    }
 
    /**
     * The main entry point
     *
     * @throws BuildException
     */
    function main()
    {
        $arguments = $this->constructArguments();
        
        if (in_array($this->phpVersion, array(5, 53))) {
            $encoderName = $this->encoderName . $this->phpVersion;
        } else {
            $encoderName = $this->encoderName;
        }
        $encoder = new PhingFile($this->ioncubePath, $encoderName);
        
        $this->log("Running ionCube Encoder...");
       
        if ($this->showCommandLine)
        {
            $this->log("Command line: ".$encoder->__toString() . ' ' . $arguments);
        }

        exec($encoder->__toString() . ' ' . $arguments . " 2>&1", $output, $return);
       
        if ($return != 0)
        {
            throw new BuildException("Could not execute ionCube Encoder: " . implode(' ', $output));
        }       
    }

    /**
     * Constructs an argument string for the ionCube encoder
     */
    private function constructArguments()
    {
        $arguments = '';
        
        foreach ($this->ionSwitches as $name => $value)
        {
            if ($value)
            {
                $arguments.= "--$name ";
            }
        }

        foreach ($this->ionOptions as $name => $value)
        {
            /**
             * action-if-no-loader value is a php source snippet so it is
	         * better to handle it this way to prevent quote problems!
	         */		
            if ($name == 'action-if-no-loader')
            {
                $arguments.= "--$name \"$value\" ";
            }
            else
            {
                $arguments.= "--$name '$value' ";
            }            
        }

        foreach ($this->ionOptionsXS as $name => $value)
        {
            foreach (explode(' ', $value) as $arg)
            {
                $arguments.= "--$name '$arg' ";
            }
        }

        foreach ($this->comments as $comment)
        {
            $arguments.= "--add-comment '" . $comment->getValue() . "' ";
        }
        
        if (!empty($this->targetOption))
        {
            switch ($this->targetOption)
            {
                case "replace":
                case "merge":
                case "update":
                case "rename":
                {
                    $arguments.= "--" . $this->targetOption . "-target ";
                } break;
                
                default:
                {
                    throw new BuildException("Unknown target option '" . $this->targetOption . "'");
                } break;
            }
        }
        
        if ($this->fromDir != '')
        {
            $arguments .= $this->fromDir . ' ';
        }

        if ($this->toDir != '')
        {
            $arguments .= "-o " . $this->toDir . ' ';
        }
       
        return $arguments;
    }
}
