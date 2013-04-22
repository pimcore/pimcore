<?php
/*
 *  $Id: 9696ab777b648a62c1c7dfde7f4da3ec9c7ec268 $
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
require_once '../classes/phing/tasks/ext/git/GitPushTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 9696ab777b648a62c1c7dfde7f4da3ec9c7ec268 $
 * @package phing.tasks.ext
 */
class GitPushTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitPushTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/repo');
    }

    public function testAllParamsSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSet');
        $this->assertInLogs('git-push: pushing to origin master:foobranch');
        $this->assertInLogs('git-push: complete');
    }

    public function testAllReposSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allReposSet');
        $this->assertInLogs('git-push: push to all refs');
        $this->assertInLogs('git-push: complete');
    }

    public function testTagsSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('tagsSet');
        $this->assertInLogs('git-push: pushing to origin master:foobranch');
        $this->assertInLogs('git-push: complete');
    }

    public function testDeleteSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('deleteSet');
        $this->assertInLogs('git-push: pushing to origin master:newbranch');
        $this->assertInLogs('git-push: branch delete requested');
        $this->assertInLogs('git-push: complete');
    }

    public function testMirrorSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('mirrorSet');
        $this->assertInLogs('git-push: mirror all refs');
        $this->assertInLogs('git-push: complete');
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testWrongRepo()
    {
        $this->expectBuildExceptionContaining('wrongRepo', 
            'Repo dir is wrong',
            'You must specify readable directory as repository.');
    }

    public function testNoDestinationSpecified()
    {
        $this->expectBuildExceptionContaining('noDestination', 
            'No source set',
            'At least one destination must be provided');
    }

}
