<?php
/*
 *  $Id: f96a4faad59ab1b29abccd59d04269fe0c409084 $
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
 * Wrapper aroung git-pull
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: f96a4faad59ab1b29abccd59d04269fe0c409084 $
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 * @since 2.4.3
 */
class GitPullTask extends GitBaseTask
{
    /**
     * <repository> argument to git-pull
     * @var string
     */
    private $source = 'origin';

    /**
     * <refspec> argument to git-pull
     * @var string
     */
    private $refspec;

    /**
     * --rebase key to git-pull
     * @var boolean
     */
    private $rebase = false;

    /**
     * --no-rebase key to git-pull
     * Allow to override --rebase (if set to default true in configuration)
     * @var boolean
     */
    private $noRebase = false;

    /**
     * Merge strategy. See -s <strategy> of git-pull
     * @var string
     */
    private $strategy;

    /**
     * -X or --strategy-option of git-pull
     * @var string
     */
    private $strategyOption;

    /**
     * Fetch all remotes
     * --all key to git-pull
     * @var boolean
     */
    private $allRemotes = false;

    /**
     * --append key to git-pull
     * @var boolean
     */
    private $append = false;

    /**
     * Keep downloaded pack
     * --keep key to git-pull
     * @var boolean
     */
    private $keepFiles = false;

    /**
     * Disable/enable automatic tag following
     * --no-tags key to git-pull
     * @var boolean
     */
    private $noTags = false;

    /**
     * Fetch all tags (even not reachable from branch heads)
     * --tags key to git-pull
     * @var boolean
     */
    private $tags = false;

    /**
     * --quiet, -q key to git-pull
     * @var boolean
     */
    private $quiet = true;

    /**
     * --force, -f key to git-pull
     * @var boolean
     */
    private $force = false;

    /**
     * Valid merge strategies
     * @var array
     */
    private $validStrategies = array(
        'octopus', 'ours', 'recursive', 'resolve', 'subtree');

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }

        $client = $this->getGitClient(false, $this->getRepository());
        $command = $client->getCommand('pull');
        $command
            ->setOption('rebase', $this->isRebase());

        if (!$this->isRebase()) {
            $command->setOption('no-rebase', $this->isNoRebase());
        }

        $strategy = $this->getStrategy();
        if ($strategy) {
            // check if strategy is valid
            if (false === in_array($strategy, $this->validStrategies)) {
                throw new BuildException(
                    "Could not find merge strategy '" . $strategy . "'\n".
                    "Available strategies are: " . implode(', ', $this->validStrategies));
            }
            $command->setOption('strategy', $strategy);
            if ($this->getStrategyOption()) {
                $command->setOption(
                    'strategy-option', $this->getStrategyOption());
            }
        }

        // order of arguments is important
        $command
            ->setOption('tags', $this->isTags())
            ->setOption('no-tags', $this->isNoTags())
            ->setOption('keep', $this->isKeepFiles())
            ->setOption('append', $this->isAppend())
            ->setOption('q', $this->isQuiet())
            ->setOption('force', $this->isForce());

        // set operation target
        if ($this->isAllRemotes()) {            // --all
            $command->setOption('all', true);
            $this->log('git-pull: fetching from all remotes', Project::MSG_INFO);
        } elseif ($this->getSource()) {         // <repository> [<refspec>]
            $command->addArgument($this->getSource());
            if ($this->getRefspec()) {
                $command->addArgument($this->getRefspec());
            }
            $this->log(
                sprintf('git-pull: pulling from %s %s', 
                    $this->getSource(), $this->getRefspec()), 
                Project::MSG_INFO); 
        } else {
            throw new BuildException('No source repository specified');
        }

        $this->log('git-pull command: ' . $command->createCommandString(), Project::MSG_INFO);

        try {
            $output = $command->execute();
        } catch (Exception $e) {
            throw new BuildException('Task execution failed.');
        }

        $this->log('git-pull: complete', Project::MSG_INFO); 
        $this->log('git-pull output: ' . trim($output), Project::MSG_INFO);

    }

    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }

    public function getStrategy()
    {
        return $this->strategy;
    }

    public function setStrategyOption($strategyOption)
    {
        $this->strategyOption = $strategyOption;
    }

    public function getStrategyOption()
    {
        return $this->strategyOption;
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

    public function setAppend($flag)
    {
        $this->append = (boolean)$flag;
    }

    public function getAppend()
    {
        return $this->append;
    }

    public function isAppend()
    {
        return $this->getAppend();
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

    public function setRebase($flag)
    {
        $this->rebase = (boolean)$flag;
    }

    public function getRebase()
    {
        return $this->rebase;
    }

    public function isRebase()
    {
        return $this->getRebase();
    }

    public function setNoRebase($flag)
    {
        $this->noRebase = (boolean)$flag;
    }

    public function getNoRebase()
    {
        return $this->noRebase;
    }

    public function isNoRebase()
    {
        return $this->getNoRebase();
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

}
