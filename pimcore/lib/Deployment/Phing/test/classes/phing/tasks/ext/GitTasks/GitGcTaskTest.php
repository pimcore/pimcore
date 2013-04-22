<?php
/*
 *  $Id: efb2b300efba7eb27bf1b9c1aa9693eb23366915 $
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
require_once '../classes/phing/tasks/ext/git/GitGcTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: efb2b300efba7eb27bf1b9c1aa9693eb23366915 $
 * @package phing.tasks.ext
 */
class GitGcTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitGcTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testAllParamsSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSet');
        $this->assertInLogs('git-gc: cleaning up "' . $repository . '" repository');
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testAutoParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $msg = 'git-gc: cleaning up "' . $repository . '" repository';

        $this->executeTarget('autoParamSet');
        $this->assertInLogs($msg);
    }

    public function testNoPruneParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $msg = 'git-gc: cleaning up "' . $repository . '" repository';

        $this->executeTarget('nopruneParamSet');
        $this->assertInLogs($msg);
    }

    public function testAggressiveParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $msg = 'git-gc: cleaning up "' . $repository . '" repository';

        $this->executeTarget('aggressiveParamSet');
        $this->assertInLogs($msg);
    }

    public function testPruneParameter()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $msg = 'git-gc: cleaning up "' . $repository . '" repository';

        $this->executeTarget('pruneParamSet');
        $this->assertInLogs($msg);
    }
}
