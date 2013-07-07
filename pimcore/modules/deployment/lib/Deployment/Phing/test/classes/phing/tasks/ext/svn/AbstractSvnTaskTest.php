<?php
/*
 *  $Id: f744ece5eb4db768ef9fdaaee820db6697cadf63 $
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
 
require_once 'phing/BuildFileTest.php';
require_once dirname(__FILE__) . '/../GitTasks/GitTestsHelper.php';

/**
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: f744ece5eb4db768ef9fdaaee820db6697cadf63 $
 * @package phing.tasks.ext
 */
abstract class AbstractSvnTaskTest extends BuildFileTest { 
    protected $backupGlobals = FALSE;
    
    protected $savedErrorLevel = 0;
     
    public function setUp($buildFilename, $createDirectory = true) { 
        if (is_readable(PHING_TEST_BASE . '/tmp/svn')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/svn');
        }
        
        if ($createDirectory) {
            // set temp directory used by test cases
            mkdir(PHING_TEST_BASE . '/tmp/svn');
        }

        $this->savedErrorLevel = error_reporting();
        error_reporting(E_ERROR);
        
        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/svn/' . $buildFilename);
    }

    public function tearDown()
    {
        error_reporting($this->savedErrorLevel);
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/svn');
    }
}
