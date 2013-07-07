<?php
/*
 *  $Id: 300efdab5b721c6312491450bc2ba93ffc8124b4 $
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

/**
 * Copy files to and from a remote host using scp.
 *
 * @author    Michiel Rook <mrook@php.net>
 * @author    Johan Van den Brande <johan@vandenbrande.com>
 * @version   $Id: 300efdab5b721c6312491450bc2ba93ffc8124b4 $
 * @package   phing.tasks.ext
 */

class ScpTask extends Task
{
    protected $file = "";
    protected $filesets = array(); // all fileset objects assigned to this task
    protected $todir = "";
    protected $mode = null;

    protected $host = "";
    protected $port = 22;
    protected $username = "";
    protected $password = "";
    protected $autocreate = true;
    protected $fetch = false;
    protected $localEndpoint = "";
    protected $remoteEndpoint = "";

    protected $pubkeyfile = '';
    protected $privkeyfile = '';
    protected $privkeyfilepassphrase = '';
       
    protected $connection = null;
    protected $sftp = null;
    
    protected $count = 0;
    
    protected $logLevel = Project::MSG_VERBOSE;

    /**
     * Sets the remote host
     */
    public function setHost($h)
    {
        $this->host = $h;
    }

    /**
     * Returns the remote host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the remote host port
     */
    public function setPort($p)
    {
        $this->port = $p;
    }

    /**
     * Returns the remote host port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the mode value
     */
    public function setMode($value)
    {
        $this->mode = $value;
    }

    /**
     * Returns the mode value
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets the username of the user to scp
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Returns the username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the password of the user to scp
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the password
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Sets the public key file of the user to scp
     */
    public function setPubkeyfile($pubkeyfile)
    {
        $this->pubkeyfile = $pubkeyfile;
    }

    /**
     * Returns the pubkeyfile
     */
    public function getPubkeyfile()
    {
        return $this->pubkeyfile;
    }
    
    /**
     * Sets the private key file of the user to scp
     */
    public function setPrivkeyfile($privkeyfile)
    {
        $this->privkeyfile = $privkeyfile;
    }

    /**
     * Returns the private keyfile
     */
    public function getPrivkeyfile()
    {
        return $this->privkeyfile;
    }
    
    /**
     * Sets the private key file passphrase of the user to scp
     */
    public function setPrivkeyfilepassphrase($privkeyfilepassphrase)
    {
        $this->privkeyfilepassphrase = $privkeyfilepassphrase;
    }

    /**
     * Returns the private keyfile passphrase
     */
    public function getPrivkeyfilepassphrase($privkeyfilepassphrase)
    {
        return $this->privkeyfilepassphrase;
    }
    
    /**
     * Sets whether to autocreate remote directories
     */
    public function setAutocreate($autocreate)
    {
        $this->autocreate = (bool) $autocreate;
    }
    
    /**
     * Returns whether to autocreate remote directories
     */
    public function getAutocreate()
    {
        return $this->autocreate;
    }
    
    /**
     * Set destination directory
     */
    public function setTodir($todir)
    {
        $this->todir = $todir;
    }

    /**
     * Returns the destination directory
     */
    public function getTodir()
    {
        return $this->todir;
    }

    /**
     * Sets local filename
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Returns local filename
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Sets whether to send (default) or fetch files
     */
    public function setFetch($fetch)
    {
        $this->fetch = (bool) $fetch;
    }
    
    /**
     * Returns whether to send (default) or fetch files
     */
    public function getFetch()
    {
        return $this->fetch;
    }
    
    /**
     * Nested creator, creates a FileSet for this task
     *
     * @return FileSet The created fileset object
     */
    public function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }
    
    /**
     * Set level of log messages generated (default = verbose)
     * @param string $level
     */
    public function setLevel($level)
    {
        switch ($level)
        {
            case "error": $this->logLevel = Project::MSG_ERR; break;
            case "warning": $this->logLevel = Project::MSG_WARN; break;
            case "info": $this->logLevel = Project::MSG_INFO; break;
            case "verbose": $this->logLevel = Project::MSG_VERBOSE; break;
            case "debug": $this->logLevel = Project::MSG_DEBUG; break;
        }
    }

    public function init()
    {
    }

    public function main()
    {
        if (!function_exists('ssh2_connect')) { 
            throw new BuildException("To use ScpTask, you need to install the PHP SSH2 extension.");
        }
        
        if ($this->file == "" && empty($this->filesets)) {
            throw new BuildException("Missing either a nested fileset or attribute 'file'");
        }
        
        if ($this->host == "" || $this->username == "") {
            throw new BuildException("Attribute 'host' and 'username' must be set");
        }

        $this->connection = ssh2_connect($this->host, $this->port);
        if (is_null($this->connection)) {
            throw new BuildException("Could not establish connection to " . $this->host . ":" . $this->port . "!");
        }

        $could_auth = null;
        if ( $this->pubkeyfile ) {
            $could_auth = ssh2_auth_pubkey_file($this->connection, $this->username, $this->pubkeyfile, $this->privkeyfile, $this->privkeyfilepassphrase);
        } else {
            $could_auth = ssh2_auth_password($this->connection, $this->username, $this->password);
        }
        if (!$could_auth) {
            throw new BuildException("Could not authenticate connection!");
        }
        
        // prepare sftp resource
        if ($this->autocreate) {
            $this->sftp = ssh2_sftp($this->connection);
        }
        
        if ($this->file != "") {
            $this->copyFile($this->file, basename($this->file));
        } else {
            if ($this->fetch) {
                throw new BuildException("Unable to use filesets to retrieve files from remote server");
            }
            
            foreach($this->filesets as $fs) {
                $ds = $fs->getDirectoryScanner($this->project);
                $files = $ds->getIncludedFiles();
                $dir = $fs->getDir($this->project)->getPath();
                foreach($files as $file) {
                    $path = $dir.DIRECTORY_SEPARATOR.$file;
                    
                    // Translate any Windows paths
                    $this->copyFile($path, strtr($file, '\\', '/'));
                }
            }
        }
        
        $this->log("Copied " . $this->counter . " file(s) " . ($this->fetch ? "from" : "to") . " '" . $this->host . "'");
        
        // explicitly close ssh connection
        @ssh2_exec($this->connection, 'exit');
    }
    
    protected function copyFile($local, $remote)
    {
        $path = rtrim($this->todir, "/") . "/";
        
        if ($this->fetch) {
            $localEndpoint = $path . $remote;
            $remoteEndpoint = $local;

            $this->log('Will fetch ' . $remoteEndpoint . ' to ' . $localEndpoint, $this->logLevel);

            $ret = @ssh2_scp_recv($this->connection, $remoteEndpoint, $localEndpoint);
            
            if ($ret === false) {
                throw new BuildException("Could not fetch remote file '" . $remoteEndpoint . "'");
            }
        } else {
            $localEndpoint = $local;
            $remoteEndpoint = $path . $remote;

            if ($this->autocreate) {
                ssh2_sftp_mkdir($this->sftp, dirname($remoteEndpoint), (is_null($this->mode) ? 0777 : $this->mode), true);
            }
            
            $this->log('Will copy ' . $localEndpoint . ' to ' . $remoteEndpoint, $this->logLevel);
            
            if (!is_null($this->mode)) {
                $ret = @ssh2_scp_send($this->connection, $localEndpoint, $remoteEndpoint, $this->mode);
            } else {
                $ret = @ssh2_scp_send($this->connection, $localEndpoint, $remoteEndpoint);
            }

            if ($ret === false) {
                throw new BuildException("Could not create remote file '" . $remoteEndpoint . "'");
            }
        }

        $this->counter++;
    }
}
