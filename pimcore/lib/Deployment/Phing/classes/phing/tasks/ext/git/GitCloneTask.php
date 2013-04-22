<?php
/*
 *  $Id: 0d9ce448c11e505885b9c5362f5c2d399e205f90 $
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
 * Wrapper around git-clone
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 0d9ce448c11e505885b9c5362f5c2d399e205f90 $
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 * @since 2.4.3
 */
class GitCloneTask extends GitBaseTask
{
    /**
     * Whether --bare key should be set for git-init
     * @var string
     */
    private $isBare = false;

    /**
     * Path to target directory
     * @var string
     */
    private $targetPath;

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }

        if (null === $this->getTargetPath()) {
            throw new BuildException('"targetPath" is required parameter');
        }

        $files = @scandir($this->getTargetPath());
        if (isset($files) && is_array($files) && (count($files) > 2)) {
            throw new BuildException(
                sprintf(
                    '"%s" target directory is not empty',
                    $this->getTargetPath())
            );
        }

        $client = $this->getGitClient(false, getcwd());

        try {
            $client->createClone(
                $this->getRepository(), 
                $this->isBare(), 
                $this->getTargetPath());
        } catch (Exception $e) {
            throw new BuildException('The remote end hung up unexpectedly');
        }

        $msg = 'git-clone: cloning ' 
            . ($this->isBare() ? '(bare) ' : '')
            . '"' . $this->getRepository() .'" repository'
            . ' to "' . $this->getTargetPath() .'" directory'; 
        $this->log($msg, Project::MSG_INFO); 
    }

    /**
     * Get path to target direcotry repo
     *
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * Set path to source repo
     *
     * @param string $targetPath Path to repository used as source
     * @return void
     */
    public function setTargetPath($targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Alias @see getBare()
     *
     * @return string
     */
    public function isBare()
    {
        return $this->getBare();
    }

    public function getBare()
    {
        return $this->isBare;
    }

    public function setBare($flag)
    {
        $this->isBare = (bool)$flag;
    }

}
