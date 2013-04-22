<?php
/**
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
 * Copies a repository from the repository url to another
 *
 * @version $Id: 6f39598901c83ecaf8e7fcb9d4065f70b38324cb $
 * @package phing.tasks.ext.svn
 * @since 2.3.0
 */
class SvnCopyTask extends SvnBaseTask
{
    private $message = "";

    /**
     * Sets the message
     */
    function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Gets the message
     */
    function getMessage()
    {
        return $this->message;
    }

    /**
     * The main entry point
     *
     * @throws BuildException
     */
    function main()
    {
        $this->setup('copy');

        $this->log("Copying SVN repository from '" . $this->getRepositoryUrl()  .  "' to '" . $this->getToDir() . "'");
        
        $options = array();
        
        if (strlen($this->getMessage()) > 0) {
            $options['message'] = $this->getMessage();
        }

        $this->run(array($this->getToDir()), $options);
    }
}

