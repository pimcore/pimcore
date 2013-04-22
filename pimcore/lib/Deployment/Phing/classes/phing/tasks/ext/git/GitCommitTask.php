<?php
/*
 *  $Id: 72b37d2b9617866aecd6355298f659a5c817b8bd $
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
require_once 'phing/tasks/ext/git/GitBaseTask.php';
/**
 * Wrapper around git-commit
 *
 * @package Phing.tasks.ext.git
 * @author Jonathan Creasy <jonathan.creasy@gmail.com>
 * @see VersionControl_Git
 * @since 2.4.3
 */
class GitCommitTask extends GitBaseTask
{
    private $allFiles;

    private $message;

    private $files;

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }

        if ($this->allFiles !== true && empty($this->files))
        {
        	throw new BuildException('"allFiles" cannot be false if no files are specified.');
        }

        $client = $this->getGitClient(false, $this->getRepository());

        $options = Array();

        if ($this->allFiles === true)
        {
        	$options['all'] = true;
        }

        $arguments = Array();
        if ($this->allFiles !== true && is_array($this->files))
        {
        	foreach($files as $file)
        	{
	        	$arguments[] = $file;
        	}
        }

        if (!empty($this->message))
        {
            $options['message'] = $this->message;
        } else {
        	$options['allow-empty-message'] = true;
        }

        try {
        	$command = $client->getCommand('commit');
        	$command->setArguments($arguments);
        	$command->setOptions($options);
        	$command->execute();
        } catch (Exception $e) {
            throw new BuildException('The remote end hung up unexpectedly');
        }

        $msg = 'git-commit: Executed git commit ';
        foreach ($options as $option=>$value)
        {

        	$msg .= ' --' . $option . '=' . $value;
        }

        foreach ($arguments as $argument)
        {
        	$msg .= ' ' . $argument;
        }

        $this->log($msg, Project::MSG_INFO);
    }

    /**
     * Alias @see getAllFiles()
     *
     * @return string
     */
    public function isallFiles()
    {
        return $this->getallFiles();
    }

    public function getallFiles()
    {
        return $this->allFiles;
    }

    public function setallFiles($flag)
    {
        $this->allFiles = (bool)$flag;
    }

    public function getMessage()
    {
    	return $this->message;
    }

    public function setMessage($message)
    {
    	$this->message = $message;
    }

    public function getFiles()
    {
    	return $this->files;
    }

    public function setFiles($files)
    {
    	if (!$empty($files) && is_array($files))
    	{
    		$this->setallfiles(false);
    		$this->Files = $files;
    	} else {
    		$this->Files = null;
    	}
    }
}
