<?php
/**
 * $Id: 2beb14d928ee47f36cceb9467b4a2ac9d2c81ef4 $
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
require_once 'phing/tasks/ext/svn/SvnBaseTask.php';

/**
 * Switches a repository at a given local directory to a different location
 *
 * @author Dom Udall <dom.udall@clock.co.uk>
 * @version $Id: 2beb14d928ee47f36cceb9467b4a2ac9d2c81ef4 $
 * @package phing.tasks.ext.svn
 * @since 2.4.3
 */
class SvnSwitchTask extends SvnBaseTask
{
    /**
     * Which Revision to Export
     *
     * @todo check if version_control_svn supports constants
     *
     * @var string
     */
    private $revision = 'HEAD';

    /**
     * The main entry point
     *
     * @throws BuildException
     */
    function main()
    {
        $this->setup('switch');

        $this->log("Switching SVN repository at '" . $this->getToDir() . "' to '" . $this->getRepositoryUrl() . "' "
          . ($this->getRevision()=='HEAD'?'':" (revision: {$this->getRevision()})"));

        // revision
        $switches = array(
            'r' => $this->getRevision(),
        );

        $this->run(array($this->getToDir()), $switches);
    }

    public function setRevision($revision)
    {
        $this->revision = $revision;
    }
    
    public function getRevision()
    {
        return $this->revision;
    }
}
