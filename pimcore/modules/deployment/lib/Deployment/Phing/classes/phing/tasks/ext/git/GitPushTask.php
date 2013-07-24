<?php
/*
 *  $Id: a01e7e9f6cc92e419e82b4e99e4a45de6a61eba8 $
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
 * Wrapper aroung git-push
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: a01e7e9f6cc92e419e82b4e99e4a45de6a61eba8 $
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 * @since 2.4.3
 * @link http://www.kernel.org/pub/software/scm/git/docs/git-push.html
 */
class GitPushTask extends GitBaseTask
{
    /**
     * Instead of naming each ref to push, specifies that all refs
     * --all key to git-push
     * @var boolean
     */
    private $allRemotes = false;

    /**
     * Mirror to remote repository
     * --mirror key to git-push
     * @var boolean
     */
    private $mirror = false;

    /**
     * Same as prefixing repos with colon
     * --delete argument to git-push
     * @var string
     */
    private $delete = false;

    /**
     * Push all refs under refs/tags
     * --tags key to git-fetch
     * @var boolean
     */
    private $tags = false;

    /**
     * <repository> argument to git-push
     * @var string
     */
    private $destination = 'origin';

    /**
     * <refspec> argument to git-push
     * @var string
     */
    private $refspec;

    /**
     * --force, -f key to git-push
     * @var boolean
     */
    private $force = false;

    /**
     * --quiet, -q key to git-push
     * @var boolean
     */
    private $quiet = true;

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }

        $client = $this->getGitClient(false, $this->getRepository());
        $command = $client->getCommand('push');
        $command
            ->setOption('tags', $this->isTags())
            ->setOption('mirror', $this->isMirror())
            ->setOption('delete', $this->isDelete())
            ->setOption('q', $this->isQuiet())
            ->setOption('force', $this->isForce());

        // set operation target
        if ($this->isAllRemotes()) {            // --all
            $command->setOption('all', true);
            $this->log('git-push: push to all refs', Project::MSG_INFO); 
        } elseif ($this->isMirror()) {         // <repository> [<refspec>]
            $command->setOption('mirror', true);
            $this->log('git-push: mirror all refs', Project::MSG_INFO); 
        } elseif ($this->getDestination()) {         // <repository> [<refspec>]
            $command->addArgument($this->getDestination());
            if ($this->getRefspec()) {
                $command->addArgument($this->getRefspec());
            }
            $this->log(
                sprintf('git-push: pushing to %s %s', 
                    $this->getDestination(), $this->getRefspec()), 
                Project::MSG_INFO); 
        } else {
            throw new BuildException('At least one destination must be provided');
        }

        $this->log('git-push command: ' . $command->createCommandString(), Project::MSG_INFO);

        try {
            $output = $command->execute();
        } catch (Exception $e) {
            throw new BuildException('Task execution failed.');
        }

        $this->log('git-push: complete', Project::MSG_INFO); 
        if ($this->isDelete()) {
            $this->log('git-push: branch delete requested', Project::MSG_INFO);
        }
        $this->log('git-push output: ' . trim($output), Project::MSG_INFO);
    }

    public function setAll($flag)
    {
        $this->allRemotes = $flag;
    }

    public function getAll()
    {
        return $this->allRemotes;
    }

    public function isAllRemotes()
    {
        return $this->getAll();
    }

    public function setMirror($flag)
    {
        $this->mirror = (boolean)$flag;
    }

    public function getMirror()
    {
        return $this->mirror;
    }

    public function isMirror()
    {
        return $this->getMirror();
    }

    public function setDelete($flag)
    {
        $this->delete = (boolean)$flag;
    }

    public function getDelete()
    {
        return $this->delete;
    }

    public function isDelete()
    {
        return $this->getDelete();
    }

    public function setTags($flag)
    {
        $this->tags = $flag;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function isTags()
    {
        return $this->getTags();
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setRefspec($spec)
    {
        $this->refspec = $spec;
    }

    public function getRefspec()
    {
        return $this->refspec;
    }

    public function setForce($flag)
    {
        $this->force = $flag;
    }

    public function getForce()
    {
        return $this->force;
    }

    public function isForce()
    {
        return $this->getForce();
    }

    public function setQuiet($flag)
    {
        $this->quiet = $flag;
    }

    public function getQuiet()
    {
        return $this->quiet;
    }

    public function isQuiet()
    {
        return $this->getQuiet();
    }




}
