<?php
/*
 *  $Id: 8089fb0bb6c5be65e766eed4e1dc30fbfecea353 $
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
require_once '../classes/phing/tasks/ext/git/GitFetchTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 8089fb0bb6c5be65e766eed4e1dc30fbfecea353 $
 * @package phing.tasks.ext
 */
class GitFetchTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitFetchTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testAllParamsSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSet');
        $this->assertInLogs('git-fetch: branch "' . $repository . '" repository');
        $this->assertInLogs('git-fetch output: '); // no output actually
    }

    public function testFetchAllRemotes()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('fetchAllRemotes');
        $this->assertInLogs('git-fetch: branch "' . $repository . '" repository');
        $this->assertInLogs('git-fetch output: Fetching origin');
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testNoTargetSpecified()
    {
        $this->expectBuildExceptionContaining('noTarget', 
            'Target is required',
            'No remote repository specified');
    }

    public function testRefspecSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('refspecSet');
        $this->assertInLogs('git-fetch: branch "' . $repository . '" repository');
        $this->assertInLogs('git-fetch output: ');
        $this->assertInLogs('Deleted branch refspec-branch');
    }
}
