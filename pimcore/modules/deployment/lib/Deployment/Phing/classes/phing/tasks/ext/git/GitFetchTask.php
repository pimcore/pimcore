<?php
/*
 *  $Id: bcddbc1cd2e77003746b048568da8111b48da2fb $
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
 * Wrapper aroung git-fetch
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: bcddbc1cd2e77003746b048568da8111b48da2fb $
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 * @since 2.4.3
 */
class GitFetchTask extends GitBaseTask
{
    /**
     * --force, -f key to git-fetch
     * @var boolean
     */
    private $force = false;

    /**
     * --quiet, -q key to git-fetch
     * @var boolean
     */
    private $quiet = false;

    /**
     * Fetch all remotes
     * --all key to git-fetch
     * @var boolean
     */
    private $allRemotes = false;

    /**
     * Keep downloaded pack
     * --keep key to git-fetch
     * @var boolean
     */
    private $keepFiles = false;

    /**
     * After fetching, remove any remote tracking branches which no longer 
     * exist on the remote. 
     * --prune key to git fetch
     * @var boolean
     */
    private $prune = false;

    /**
     * Disable/enable automatic tag following
     * --no-tags key to git-fetch
     * @var boolean
     */
    private $noTags = false;

    /**
     * Fetch all tags (even not reachable from branch heads)
     * --tags key to git-fetch
     * @var boolean
     */
    private $tags = false;

    /**
     * <group> argument to git-fetch
     * @var string
     */
    private $group;

    /**
     * <repository> argument to git-fetch
     * @var string
     */
    private $source = 'origin';

    /**
     * <refspec> argument to git-fetch
     * @var string
     */
    private $refspec;

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }

        $client = $this->getGitClient(false, $this->getRepository());
        $command = $client->getCommand('fetch');
        $command
            ->setOption('tags', $this->isTags())
            ->setOption('no-tags', $this->isNoTags())
            ->setOption('prune', $this->isPrune())
            ->setOption('keep', $this->isKeepFiles())
            ->setOption('q', $this->isQuiet())
            ->setOption('force', $this->isForce());

        // set operation target
        if ($this->isAllRemotes()) {            // --all
            $command->setOption('all', true);
        } elseif ($this->getGroup()) {          // <group>
            $command->addArgument($this->getGroup());
        } elseif ($this->getSource()) {         // <repository> [<refspec>]
            $command->addArgument($this->getSource());
            if ($this->getRefspec()) {
                $command->addArgument($this->getRefspec());
            }
        } else {
            throw new BuildException('No remote repository specified');
        }

        $this->log('git-fetch command: ' . $command->createCommandString(), Project::MSG_INFO);

        try {
            $output = $command->execute();
        } catch (Exception $e) {
            throw new BuildException('Task execution failed.');
        }

        $this->log(
            sprintf('git-fetch: branch "%s" repository', $this->getRepository()), 
            Project::MSG_INFO); 
        $this->log('git-fetch output: ' . trim($output), Project::MSG_INFO);
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

    public function setKeep($flag)
    {
        $this->keepFiles = $flag;
    }

    public function getKeep()
    {
        return $this->keepFiles;
    }

    public function isKeepFiles()
    {
        return $this->getKeep();
    }

    public function setPrune($flag)
    {
        $this->prune = $flag;
    }

    public function getPrune()
    {
        return $this->prune;
    }

    public function isPrune()
    {
        return $this->getPrune();
    }
    
    public function setNoTags($flag)
    {
        $this->noTags = $flag;
    }

    public function getNoTags()
    {
        return $this->noTags;
    }

    public function isNoTags()
    {
        return $this->getNoTags();
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

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setRefspec($spec)
    {
        $this->refspec = $spec;
    }

    public function getRefspec()
    {
        return $this->refspec;
    }

    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }

}
