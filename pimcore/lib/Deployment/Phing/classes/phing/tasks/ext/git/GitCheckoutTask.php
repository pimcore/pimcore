<?php
/*
 *  $Id: a1dcb809b44bfd34c3af154683dd2200c814e5f0 $
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
 * Wrapper around git-checkout
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: a1dcb809b44bfd34c3af154683dd2200c814e5f0 $
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 * @since 2.4.3
 */
class GitCheckoutTask extends GitBaseTask
{
    /**
     * Branch name
     * @var string
     */
    private $branchname;

    /**
     * If not HEAD, specify starting point
     * @var string
     */
    private $startPoint;

    /**
     * --force, -f key to git-checkout
     * @var boolean
     */
    private $force = false;

    /**
     * --quiet, -q key to git-checkout
     * @var boolean
     */
    private $quiet = false;

    /**
     * When creating a new branch, set up "upstream" configuration.
     * --track key to git-checkout
     * @var boolean
     */
    private $track = false;

    /**
     * Do not set up "upstream" configuration
     * --no-track key to git-checkout
     * @var boolean
     */
    private $noTrack = false;

    /**
     * -b, -B, -m  options to git-checkout
     * Respective task options:
     * create, forceCreate, merge
     * @var array
     */
    private $extraOptions = array(
        'b' => false,
        'B' => false,
        'm' => false,
    );

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository()) {
            throw new BuildException('"repository" is required parameter');
        }
        if (null === $this->getBranchname()) {
            throw new BuildException('"branchname" is required parameter');
        }

        $client = $this->getGitClient(false, $this->getRepository());
        $command = $client->getCommand('checkout');
        $command
            ->setOption('no-track', $this->isNoTrack())
            ->setOption('q', $this->isQuiet())
            ->setOption('force', $this->isForce())
            ->setOption('b', $this->isCreate())
            ->setOption('B', $this->isForceCreate())
            ->setOption('m', $this->isMerge());
        if ($this->isNoTrack()) {
            $command->setOption('track', $this->isTrack());
        }

        $command->addArgument($this->getBranchname());

        if (null !== $this->getStartPoint()) {
            $command->addArgument($this->getStartPoint());
        }

        $this->log('git-checkout command: ' . $command->createCommandString(), Project::MSG_INFO);

        try {
            $output = $command->execute();
        } catch (Exception $e) {
            throw new BuildException('Task execution failed.');
        }

        $this->log(
            sprintf('git-checkout: checkout "%s" repository', $this->getRepository()), 
            Project::MSG_INFO); 
        $this->log('git-checkout output: ' . trim($output), Project::MSG_INFO);
    }

    public function setBranchname($branchname)
    {
        $this->branchname = $branchname;
    }

    public function getBranchname()
    {
        return $this->branchname;
    }

    public function setStartPoint($startPoint)
    {
        $this->startPoint = $startPoint;
    }

    public function getStartPoint()
    {
        return $this->startPoint;
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

    public function setTrack($flag)
    {
        $this->track = $flag;
    }

    public function getTrack()
    {
        return $this->track;
    }

    public function isTrack()
    {
        return $this->getTrack();
    }

    public function setNoTrack($flag)
    {
        $this->noTrack = $flag;
    }

    public function getNoTrack()
    {
        return $this->noTrack;
    }

    public function isNoTrack()
    {
        return $this->getNoTrack();
    }
    
    public function setCreate($flag)
    {
        $this->extraOptions['b'] = $flag;
    }
    
    public function getCreate()
    {
        return $this->extraOptions['b'];
    }

    public function isCreate()
    {
        return $this->getCreate();
    }

    // -B flag is not found in all versions of git
    // --force is present everywhere
    public function setForceCreate($flag)
    {
        $this->setForce($flag);
    }
    
    public function getForceCreate()
    {
        return $this->extraOptions['B'];
    }

    public function isForceCreate()
    {
        return $this->getForceCreate();
    }

    public function setMerge($flag)
    {
        $this->extraOptions['m'] = $flag;
    }
    
    public function getMerge()
    {
        return $this->extraOptions['m'];
    }

    public function isMerge()
    {
        return $this->getMerge();
    }
}
