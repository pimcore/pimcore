<?php
/*
 *  $Id: 10592c04c39cb05c11a772bdecb50fdb47e790f5 $
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
require_once '../classes/phing/tasks/ext/git/GitCheckoutTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 10592c04c39cb05c11a772bdecb50fdb47e790f5 $
 * @package phing.tasks.ext
 */
class GitCheckoutTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitCheckoutTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testCheckoutExistingBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('checkoutExistingBranch');
        $this->assertInLogs('git-checkout: checkout "' . $repository . '" repository');
        $this->assertInLogs('git-branch output: Branch co-branch set up to track remote branch master from origin.');
        // @todo - actually make sure that Ebihara updates code to return (not
        // echo output from $command->execute()
        //$this->assertInLogs("Switched to branch 'test'"); 
        $this->assertInLogs('git-checkout output: '); // no output actually
    }

    public function testCheckoutNonExistingBranch()
    {
        $this->expectBuildExceptionContaining('checkoutNonExistingBranch', 
            'Checkout of non-existent repo is impossible',
            'Task execution failed');
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
            'Branchname is required',
            '"branchname" is required parameter');
    }

    public function testCheckoutMerge()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('checkoutMerge');
        $this->assertInLogs('git-checkout: checkout "' . $repository . '" repository');
        $this->assertInLogs('git-branch output: Branch co-branch set up to track remote branch master from origin.');
        $this->assertInLogs('git-branch output: Deleted branch master');
    }

    public function testCheckoutCreateBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('checkoutCreateBranch');
        $this->assertInLogs('git-checkout: checkout "' . $repository . '" repository');
        $this->assertInLogs('git-checkout output: Branch co-create-branch set up to track remote branch master from origin.');
        $this->assertInLogs('git-branch output: Deleted branch co-create-branch');
    }

    public function testForceCheckoutCreateBranch()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('checkoutForceCreateBranch');
        $this->assertInLogs('git-checkout: checkout "' . $repository . '" repository');
        $this->assertInLogs('git-branch output: Deleted branch co-create-branch');
    }

    public function testForceCheckoutCreateBranchFailed()
    {
        $this->expectBuildExceptionContaining('checkoutForceCreateBranchFailed', 
            'Branch already exists',
            'Task execution failed.');
    }


}
