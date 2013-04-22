<?php
/*
 *  $Id: f13507509db5e53ff5d003d073e2e66f720190f5 $
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
require_once '../classes/phing/tasks/ext/git/GitTagTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: f13507509db5e53ff5d003d073e2e66f720190f5 $
 * @package phing.tasks.ext
 */
class GitTagTaskTest extends BuildFileTest { 

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitTagTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testGitTagCreate()
    {
        $this->executeTarget('gitTagCreate');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testGitTagReplaceCreateDuplicate()
    {
        $this->executeTarget('gitTagReplaceCreateDuplicate');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -f \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testGitTagForceCreateDuplicate()
    {
        $this->executeTarget('gitTagForceCreateDuplicate');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -f \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testGitTagCreateDuplicate()
    {
        $this->expectBuildExceptionContaining('gitTagCreateDuplicate', 
            'Tag already exists',
            "fatal: tag 'ver1.0' already exists");
    }

    public function testTagCreateAnnotatedNoMessage()
    {
        $this->expectBuildExceptionContaining('gitTagCreateAnnotatedNoMessage', 
            'Message not provided..',
            '"message" or "file" required to make a tag');
    }

    public function testTagCreateAnnotated()
    {
        $this->executeTarget('gitTagCreateAnnotated');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -a -m\'Version 1.0 tag\' \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testTagCreateAnnotatedImplicit()
    {
        $this->executeTarget('gitTagCreateAnnotatedImplicit');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -m\'Version 1.0 tag\' \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testTagDelete()
    {
        $this->executeTarget('gitTagDelete');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver2.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -d \'ver2.0\'');
        $this->assertInLogs('git-tag output: Deleted tag \'ver2.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs(' ver1.0');
        $this->assertNotInLogs("\n" . 'ver2.0');
    }

    public function testTagListByPattern()
    {
        $this->executeTarget('gitTagListByPattern');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver2.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'marked\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l \'marked\'');
        $this->assertInLogs('git-tag output: marked');
    }

    public function testTagOutputPropertySet()
    {
        $this->executeTarget('gitTagOutpuPropertySet');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver2.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'marked\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l \'marked\'');
        $this->assertInLogs('git-tag output: marked');
        $this->assertPropertyEquals('gitLogOutput', 'marked' . "\n");
    }

    public function testTagWithCommitSet()
    {
        $this->executeTarget('gitTagWithCommitSet');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver1.0\' \'c573116f395d36497a1ac1dba565ecd3d3944277\'');
        $this->assertInLogs('c573116f395d36497a1ac1dba565ecd3d3944277');
        $this->assertInLogs('b8cddb3fa5f408560d0d00d6c8721fe333895888');
        $this->assertInLogs('6dbaf4508e75dcd426b5b974a67c462c70d46e1f');
        $this->assertNotInLogs('1b767b75bb5329f4e53345c516c0a9f4ed32d330');
        $this->assertNotInLogs('ee07085160003ffd1100867deb6059bae0c45455');
    }

    public function testTagWithObjectSet()
    {
        $this->executeTarget('gitTagWithObjectSet');
        $this->assertInLogs('git-tag command: /usr/bin/git tag \'ver1.0\' \'c573116f395d36497a1ac1dba565ecd3d3944277\'');
        $this->assertInLogs('c573116f395d36497a1ac1dba565ecd3d3944277');
        $this->assertInLogs('b8cddb3fa5f408560d0d00d6c8721fe333895888');
        $this->assertInLogs('6dbaf4508e75dcd426b5b974a67c462c70d46e1f');
        $this->assertNotInLogs('1b767b75bb5329f4e53345c516c0a9f4ed32d330');
        $this->assertNotInLogs('ee07085160003ffd1100867deb6059bae0c45455');
    }

    public function testTagCreateSignedDefaultKey()
    {
        $this->markTestSkipped('Involves configured GPG key');
        $this->executeTarget('gitTagCreateSignedDefaultKey');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -m\'Version 1.0 tag\' \'ver1.0\'');
        $this->assertInLogs('git-tag command: /usr/bin/git tag -l');
        $this->assertInLogs('git-tag output: ver1.0');
    }

    public function testTagFileSet()
    {
        $msgFile = PHING_TEST_BASE . '/tmp/msg.txt';
        $fp = fopen($msgFile, 'w');
        fwrite($fp , 'test tag message');
        fclose($fp);

        $this->executeTarget('gitTagFileSet');
        $this->assertInLogs("/usr/bin/git tag -F'{$msgFile}' 'ver1.0'");

        unlink($msgFile);
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }


}
