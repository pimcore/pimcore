<?php
/*
 *  $Id: df756c0703b86a9e5e71ba2e0c830acc94f0844d $
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
require_once '../classes/phing/tasks/ext/git/GitBranchTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: df756c0703b86a9e5e71ba2e0c830acc94f0844d $
 * @package phing.tasks.ext
 */
class GitBranchTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git', 0777, true);

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitBranchTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testAllParamsSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSet');
        $this->assertInLogs('git-branch output: Branch all-params-set set up to track remote branch master from origin.');
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testNoBranchnameSpecified()
    {
        $this->expectBuildExceptionContaining('noBranchname', 
            'Branchname dir is required',
            '"branchname" is required parameter');
    }

    public function testTrackParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('trackParamSet');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        $this->assertInLogs( 'git-branch output: Branch track-param-set set up to track local branch master.');
    }

    public function testNoTrackParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('noTrackParamSet');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        $this->assertInLogs('git-branch output: '); // no output actually
    }

    public function testSetUpstreamParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('setUpstreamParamSet');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        $this->assertInLogs('Branch set-upstream-param-set set up to track local branch master.'); // no output actually
    }

    public function testForceParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('forceParamSet');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        $this->assertInLogs('git-branch output: '); // no output actually
    }

    public function testDeleteBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('deleteBranch');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        $this->assertInLogs('Branch delete-branch-1 set up to track local branch master.');
        $this->assertInLogs('Branch delete-branch-2 set up to track local branch master.');
        $this->assertInLogs('Deleted branch delete-branch-1');
        $this->assertInLogs('Deleted branch delete-branch-2');
    }

    public function testMoveBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('moveBranch');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        // try to delete new branch (thus understanding that rename worked)
        $this->assertInLogs('Deleted branch move-branch-2');
    }

    public function testForceMoveBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->executeTarget('forceMoveBranch');
        $this->assertInLogs('git-branch: branch "' . $repository . '" repository');
        // try to delete new branch (thus understanding that rename worked)
        $this->assertInLogs('Deleted branch move-branch-2');
    }

    public function testForceMoveBranchNoNewbranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->expectBuildExceptionContaining('forceMoveBranchNoNewbranch', 
            'New branch name is required in branch move',
            '"newbranch" is required parameter');
    }

    public function testMoveBranchNoNewbranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';

        $this->expectBuildExceptionContaining('moveBranchNoNewbranch', 
            'New branch name is required in branch move',
            '"newbranch" is required parameter');
    }


}
