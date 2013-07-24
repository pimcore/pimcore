<?php
/*
 *  $Id: 1cf90cf9517fd20c05d0c4963d76af2ea12a339c $
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
require_once '../classes/phing/tasks/ext/git/GitLogTask.php';
require_once dirname(__FILE__) . '/GitTestsHelper.php';

/**
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 1cf90cf9517fd20c05d0c4963d76af2ea12a339c $
 * @package phing.tasks.ext
 */
class GitLogTaskTest extends BuildFileTest { 

    private $testCommits = array(
        array(
            'commit'    => '6dbaf4508e75dcd426b5b974a67c462c70d46e1f',
            'author'    => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'      => 'Sun Sep 26 21:14:44 2010 +0400',
            'msg'       => 'Inited',
            'msg-full'  => '',
            'From'      => '6dbaf4508e75dcd426b5b974a67c462c70d46e1f Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      =>  'Sun, 26 Sep 2010 21:14:44 +0400',
            'Subject'   => '[PATCH] Inited',
        ),
        array(
            'commit'    => 'b8cddb3fa5f408560d0d00d6c8721fe333895888',
            'author'    => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'      =>   'Sun Jan 23 22:53:07 2011 +0300',
            'msg'       => 'Added file1 + file2',
            'msg-full'  => '',
            'From'      => 'b8cddb3fa5f408560d0d00d6c8721fe333895888 Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      =>  'Sun, 23 Jan 2011 22:53:07 +0300',
            'Subject'   => '[PATCH] Added file1 + file2',
        ),
        array(
            'commit'    => 'c573116f395d36497a1ac1dba565ecd3d3944277',
            'author'    => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'      =>   'Sun Jan 23 22:53:19 2011 +0300',
            'msg'       => 'Added file3',
            'msg-full'  => '',
            'From'      => 'c573116f395d36497a1ac1dba565ecd3d3944277 Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      =>  'Sun, 23 Jan 2011 22:53:19 +0300',
            'Subject'   => '[PATCH] Added file3',
        ),
        array(
            'commit'    => '2b4a5409bf60813b6a84d583bbdcbed25c7c3a00',
            'author'    => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'      => 'Sun Jan 23 22:53:42 2011 +0300',
            'msg'       => 'Removed file3',
            'msg-full'  => '',
            'From'      => '2b4a5409bf60813b6a84d583bbdcbed25c7c3a00 Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      =>  'Sun, 23 Jan 2011 22:53:42 +0300',
            'Subject'   => '[PATCH] Removed file3',
        ),
        array(
            'commit'    => 'ee07085160003ffd1100867deb6059bae0c45455',
            'author'    => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'      =>   'Sun Jan 23 23:38:34 2011 +0300',
            'msg'       => 'Title: file4 was added',
            'msg-full'  => 'Full commit message: and here goes some elaboration on what has been done.',
            'From'      => 'ee07085160003ffd1100867deb6059bae0c45455 Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      =>  'Sun, 23 Jan 2011 23:38:34 +0300',
            'Subject'   => '[PATCH] Title: file4 was added',
        ),
        array(
            'commit' => '1b767b75bb5329f4e53345c516c0a9f4ed32d330',
            'author' => 'Victor Farazdagi <simple.square@gmail.com>',
            'date'   => 'Mon Jan 24 09:58:33 2011 +0300',
            'msg'   => 'Added file5', 
            'msg-full'  => 'This file was added one day after file1, file2, file3 and file4 were added',
            'From'      => '1b767b75bb5329f4e53345c516c0a9f4ed32d330 Mon Sep 17 00:00:00 2001',
            'From:'     => 'Victor Farazdagi <simple.square@gmail.com>',
            'Date'      => 'Mon, 24 Jan 2011 09:58:33 +0300',
            'Subject'   => '[PATCH] Added file5',
        ),
    
    );

    public function setUp() { 
        if (is_readable(PHING_TEST_BASE . '/tmp/git')) {
            // make sure we purge previously created directory
            // if left-overs from previous run are found
            GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
        }
        // set temp directory used by test cases
        mkdir(PHING_TEST_BASE . '/tmp/git');

        $this->configureProject(PHING_TEST_BASE 
                              . '/etc/tasks/ext/git/GitLogTaskTest.xml');
    }

    public function tearDown()
    {
        GitTestsHelper::rmdir(PHING_TEST_BASE . '/tmp/git');
    }

    public function testGitLogWithoutParams()
    {
        $this->executeTarget('gitLogWithoutParams');
        foreach($this->testCommits as $commit) {
            $this->assertInLogs($commit['date']);
            $this->assertInLogs($commit['author']);
            $this->assertInLogs($commit['commit']);
            $this->assertInLogs($commit['msg']);
            if (strlen($commit['msg-full'])) {
                $this->assertInLogs($commit['msg-full']);
            }
        }
    }

    public function testGitWithMostParams()
    {
        $this->executeTarget('gitLogWithMostParams');
        $lastTwoCommits = array_slice($this->testCommits, -2);
        $allOtherCommits = array_slice($this->testCommits, 0, -2);
        
        // test max-count
        foreach($lastTwoCommits as $commit) {
            $this->assertInLogs($commit['commit']);
            $this->assertInLogs($commit['msg']);
        }
        foreach($allOtherCommits as $commit) {
            $this->assertNotInLogs($commit['commit']);
            $this->assertNotInLogs($commit['msg']);
        }

        $this->assertInLogs('0 files changed');

    }

    public function testGitOutputPropertySet()
    {
        $this->executeTarget('gitLogOutputPropertySet');
        $this->assertPropertyEquals('gitLogOutput', '1b767b75bb5329f4e53345c516c0a9f4ed32d330 Added file5' . "\n");
    }

    public function testGitLogNameStatus()
    {
        $this->executeTarget('gitLogNameStatusSet');
        $this->assertInLogs("A\tfile1");
        $this->assertInLogs("A\tfile2");
        $this->assertInLogs("A\tfile3");
        $this->assertInLogs("A\tREADME");
        $this->assertInLogs("D\tfile3");
    }

    /**
     * @todo Need to implement the Git relative date calculation
     */
    public function testGitDateRelative()
    {
        $this->markTestSkipped('Need to implement the Git relative date calculation');
        $this->executeTarget('gitLogDateRelative');
        foreach($this->testCommits as $commit) {
            $timestamp = strtotime($commit['date']);
            $this->assertInLogs(GitTestsHelper::getRelativeDate($timestamp));
        }
    }

    public function testGitSinceUntilSet()
    {
        $this->executeTarget('gitLogSinceUntilSet');
        $this->assertNotInLogs('6dbaf4508e75dcd426b5b974a67c462c70d46e1f Inited');
        $this->assertNotInLogs('1b767b75bb5329f4e53345c516c0a9f4ed32d330 Added file5');
        $this->assertInLogs('ee07085160003ffd1100867deb6059bae0c45455 Title: file4 was added');
        $this->assertInLogs('2b4a5409bf60813b6a84d583bbdcbed25c7c3a00 Removed file3');
        $this->assertInLogs('c573116f395d36497a1ac1dba565ecd3d3944277 Added file3');
        $this->assertInLogs('b8cddb3fa5f408560d0d00d6c8721fe333895888 Added file1 + file2');
    }
    
    public function testGitBeforeAfterSet()
    {
        $this->executeTarget('gitLogBeforeAfterSet');
        $this->assertNotInLogs('6dbaf4508e75dcd426b5b974a67c462c70d46e1f Inited');
        $this->assertNotInLogs('1b767b75bb5329f4e53345c516c0a9f4ed32d330 Added file5');
        $this->assertInLogs('ee07085160003ffd1100867deb6059bae0c45455 Title: file4 was added');
        $this->assertInLogs('2b4a5409bf60813b6a84d583bbdcbed25c7c3a00 Removed file3');
        $this->assertInLogs('c573116f395d36497a1ac1dba565ecd3d3944277 Added file3');
        $this->assertInLogs('b8cddb3fa5f408560d0d00d6c8721fe333895888 Added file1 + file2');
    }

    public function testGitFormatOneLine()
    {
        $this->executeTarget('gitLogFormatOneLine');
        foreach($this->testCommits as $commit) {
            $this->assertNotInLogs($commit['author']);
            $this->assertNotInLogs($commit['date']);
            $this->assertInLogs($commit['commit']);
            $this->assertInLogs($commit['msg']);
        }
    }

    public function testGitFormatShort()
    {
        $this->executeTarget('gitLogFormatShort');
        foreach($this->testCommits as $commit) {
            $this->assertNotInLogs($commit['date']);
            $this->assertInLogs($commit['author']);
            $this->assertInLogs($commit['commit']);
            $this->assertInLogs($commit['msg']);
        }
    }

    public function testGitFormatMedium()
    {
        $this->executeTarget('gitLogFormatMedium');
        foreach($this->testCommits as $commit) {
            $this->assertInLogs($commit['date']);
            $this->assertInLogs($commit['author']);
            $this->assertInLogs($commit['commit']);
            $this->assertInLogs($commit['msg']);
            if (strlen($commit['msg-full'])) {
                $this->assertInLogs($commit['msg-full']);
            }
        }
    }

    public function testGitFormatFull()
    {
        $this->executeTarget('gitLogFormatFull');
        foreach($this->testCommits as $commit) {
            $this->assertNotInLogs($commit['date']);
            $this->assertInLogs('Author: ' . $commit['author']);
            $this->assertInLogs('Commit: ' . $commit['author']);
            $this->assertInLogs('commit ' . $commit['commit']);
            $this->assertInLogs($commit['msg']);
            if (strlen($commit['msg-full'])) {
                $this->assertInLogs($commit['msg-full']);
            }
        }
    }

    public function testGitFormatFuller()
    {
        $this->executeTarget('gitLogFormatFuller');
        foreach($this->testCommits as $commit) {
            $this->assertInLogs('Author:     ' . $commit['author']);
            $this->assertInLogs('AuthorDate: ' . $commit['date']);
            $this->assertInLogs('Commit:     ' . $commit['author']);
            $this->assertInLogs('CommitDate: ' . $commit['date']);
            $this->assertInLogs('commit ' . $commit['commit']);
            $this->assertInLogs($commit['msg']);
            if (strlen($commit['msg-full'])) {
                $this->assertInLogs($commit['msg-full']);
            }
        }
    }

    public function testGitFormatEmail()
    {
        $this->executeTarget('gitLogFormatEmail');
        foreach($this->testCommits as $commit) {
            $this->assertInLogs('From ' . $commit['From']);
            $this->assertInLogs('From: ' . $commit['From:']);
            $this->assertInLogs('Date: ' . $commit['Date']);
            $this->assertInLogs('Subject: ' . $commit['Subject']);
            if (strlen($commit['msg-full'])) {
                $this->assertInLogs($commit['msg-full']);
            }
        }
    }

    public function testGitFormatCustom()
    {
        $this->executeTarget('gitLogFormatCustom');
        foreach($this->testCommits as $commit) {
            $this->assertInLogs(
                sprintf('The author of %s was %s', $commit['commit'], $commit['author']));
        }
    }



    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repo dir is required',
            '"repository" is required parameter');
    }


}
