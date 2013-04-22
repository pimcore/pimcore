<?php
/*
 *  $Id: 40b278b3141d96029cf889b01b3e7b68c8168605 $
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
require_once '../classes/phing/tasks/ext/git/GitPullTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 40b278b3141d96029cf889b01b3e7b68c8168605 $
 * @package phing.tasks.ext
 */
class GitPullTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitPullTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testAllParamsSet()
    {
        /*$repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSet');
        $this->assertInLogs('git-pull: pulling from origin foobranch');
        $this->assertInLogs('git-pull: complete');
        $this->assertInLogs('git-pull output: Updating 6dbaf45..6ad2ea3');
        // make sure that foofile from foobranch made it to master
        $this->assertTrue(is_readable($repository . '/foofile'));*/
    }

    public function testAllParamsSetRebase()
    {
        /*$repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allParamsSetRebase');
        $this->assertInLogs('git-pull: pulling from origin foobranch');
        $this->assertInLogs('git-pull: complete');
        $this->assertInLogs('git-pull output: First, rewinding head to replay your work on top of it...');
        $this->assertInLogs('Fast-forwarded master to 6ad2ea37a26ce3534073e89043f890c054fddb20.');
        // make sure that foofile from foobranch made it to master
        $this->assertTrue(is_readable($repository . '/foofile'));*/
    }

    public function testAllReposSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('allReposSet');
        $this->assertInLogs('git-pull: fetching from all remotes');
        $this->assertInLogs('git-pull: complete');
    }

    public function testTagsSet()
    {
        /*$repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('tagsSet');
        $this->assertInLogs('git-pull: pulling from origin foobranch');
        $this->assertInLogs('git-pull: complete');
        $this->assertInLogs('git-pull output: Updating 6dbaf45..6ad2ea3');
        // make sure that foofile from foobranch made it to master
        $this->assertTrue(is_readable($repository . '/foofile'));*/
    }

    public function testAppendSet()
    {
        $repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('appendSet');
        $this->assertInLogs('git-pull: fetching from all remotes');
        $this->assertInLogs('git-pull: complete');
        $this->assertInLogs('git-pull output: Already up-to-date.');
    }

    public function testNoTagsSet()
    {
        /*$repository = PHING_TEST_BASE . '/tmp/git';
        $this->executeTarget('noTagsSet');
        $this->assertInLogs('git-pull: pulling from origin foobranch');
        $this->assertInLogs('git-pull: complete');
        $this->assertInLogs('git-pull output: Updating 6dbaf45..6ad2ea3');
        // make sure that foofile from foobranch made it to master
        $this->assertTrue(is_readable($repository . '/foofile'));*/
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }

    public function testNoSourceSpecified()
    {
        $this->expectBuildExceptionContaining('noSource', 
            'At least one source must be provided',
            'No source repository specified');
    }

    public function testWrongStrategySet()
    {
        $this->expectBuildExceptionContaining('wrongStrategySet', 
            'Wrong strategy passed', 'Could not find merge strategy \'plain-wrong\'');
    }
}
