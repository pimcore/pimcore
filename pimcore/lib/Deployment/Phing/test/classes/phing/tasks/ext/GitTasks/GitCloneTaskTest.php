<?php
/*
 *  $Id: 535d863a2001a5c9381b460adfd17a6f78aeb70f $
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
require_once '../classes/phing/tasks/ext/git/GitCloneTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 535d863a2001a5c9381b460adfd17a6f78aeb70f $
 * @package phing.tasks.ext
 */
class GitCloneTaskTest extends BuildFileTest { 

    public function setUp() { 
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitCloneTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testWrongRepository()
    {
        $this->expectBuildExceptionContaining('wrongRepository', 
            'Repository not readable', 
            'The remote end hung up unexpectedly');
    }

    public function testGitClone()
    {
        $bundle = PHING_TEST_BASE . '/etc/tasks/ext/git/phing-tests.git';
        $repository = PHING_TEST_BASE . '/tmp/git';
        $gitFilesDir = $repository . '/.git';
        $this->executeTarget('gitClone');

        $this->assertInLogs('git-clone: cloning "' . $bundle . '" repository to "' . $repository . '" directory');
        $this->assertTrue(is_dir($repository));
        $this->assertTrue(is_dir($gitFilesDir));
        // test that file is actully cloned
        $this->assertTrue(is_readable($repository . '/README'));
    }

    public function testGitCloneBare()
    {
        $bundle = PHING_TEST_BASE . '/etc/tasks/ext/git/phing-tests.git';
        $repository = PHING_TEST_BASE . '/tmp/git';
        $gitFilesDir = $repository . '/.git';
        $this->executeTarget('gitCloneBare');
        $this->assertInLogs('git-clone: cloning (bare) "' . $bundle . '" repository to "' . $repository . '" directory');
        $this->assertTrue(is_dir($repository));
        $this->assertTrue(is_dir($repository . '/branches'));
        $this->assertTrue(is_dir($repository . '/info'));
        $this->assertTrue(is_dir($repository . '/hooks'));
        $this->assertTrue(is_dir($repository . '/refs'));
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testNoTargetPathSpecified()
    {
        $this->expectBuildExceptionContaining('noTargetPath', 
            'Target path is required',
            '"targetPath" is required parameter');
    }


}
