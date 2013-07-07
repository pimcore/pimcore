<?php
/**
 * Copyright (c) 2012, Laurent Laville <pear@laurent-laville.org>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category   Tasks
 * @package    Phing
 * @subpackage GrowlNotify
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       https://github.com/llaville/phing-GrowlNotifyTask
 */

/**
 * Tests for GrowlNotifyTask
 *
 * @category   Tasks
 * @package    Phing
 * @subpackage GrowlNotify
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       https://github.com/llaville/phing-GrowlNotifyTask
 */
class GrowlNotifyTaskTest extends BuildFileTest
{
    /**
     * Mock task's instance.
     *
     * @var object
     */
    protected $mockTask;

    /**
     * Sets up the fixture.
     *
     * @return void
     */
    public function setUp() 
    {
        $this->configureProject(PHING_TEST_BASE . '/etc/tasks/ext/growl/build.xml');

        $name = '';
        
        $gntpMock = Net_Growl::singleton(
            $name, array(), '', array('protocol' => 'gntpMock')
        );
        $gntpMock->addResponse(
            "GNTP/1.0 -OK NONE\r\n" .
            "Response-Action: REGISTER\r\n" .
            ""
        );
        $gntpMock->addResponse(
            "GNTP/1.0 -OK NONE\r\n" .
            "Response-Action: NOTIFY\r\n" .
            ""
        );
        $this->mockTask = new GrowlNotifyTask($gntpMock);
        $this->mockTask->setProject($this->project);
        $targets = $this->project->getTargets();
        $targets['test']->addTask($this->mockTask);
        $this->mockTask->setOwningTarget($targets['test']);
    }

    /**
     * Test for required message attribute
     * 
     * @expectedException BuildException
     * @return void
     */
    public function testEmptyMessage()
    {
        $this->executeTarget(__FUNCTION__);
    }

    /**
     * Test a single message notification
     *
     * @return void
     */
    public function testSingleNotification()
    {
        $this->mockTask->setMessage('Single test message.');

        $this->executeTarget('test');
        $this->assertInLogs('Notification-Text: Single test message.');
    }

    /**
     * Test a single message notification that sould be sticky
     *
     * @return void
     */
    public function testSingleStickyNotification()
    {
        $this->mockTask->setMessage('Sticky message !!!');
        $this->mockTask->setSticky(true);
        
        $this->executeTarget('test');
        $this->assertInLogs('Notification-Sticky: 1', Project::MSG_DEBUG);
    }

    /**
     * Test a single notification with custom application icon
     *
     * @return void
     */
    public function testSingleCustomAppIconNotification()
    {
        $this->mockTask->setMessage('Test with custom Application Icon.');
        $this->mockTask->setAppicon('..\..\..\..\data\Help.ico');

        $this->executeTarget('test');
        $this->assertInLogs('Application-Icon:', Project::MSG_DEBUG);
    }

    /**
     * Test a single notification with custom notification type
     *
     * @return void
     */
    public function testSingleNotificationType() 
    {
        $this->mockTask->setMessage('Build FINISHED.');
        $this->mockTask->setNotification('Status');

        $this->executeTarget('test');
        $this->assertInLogs('Notification-Name: Status', Project::MSG_DEBUG);
    }

    /**
     * Test a single notification with custom title
     *
     * @return void
     */
    public function testSingleNotificationTitled()
    {
        $this->mockTask->setMessage('Build FAILED.');
        $this->mockTask->setTitle('PhingNotify');

        $this->executeTarget('test');
        $this->assertInLogs('Notification-Title: PhingNotify', Project::MSG_DEBUG);
    }

    /**
     * Test broadcasting message
     *
     * @return void
     */
    public function testBroadcastNotification()
    {
        $this->mockTask->setMessage('Broadcast message : Build FAILED.');
        $this->mockTask->setHost('192.168.1.2');

        $this->executeTarget('test');
        $this->assertInLogs('Notification was sent to remote host 192.168.1.2');
    }

    /**
     * Test a single notification with priority defined
     *
     * @return void
     */
    public function testSingleNotificationWithPriority()
    {
        $this->mockTask->setMessage('Build DEPLOYED.');
        $this->mockTask->setPriority('high');

        $this->executeTarget('test');
        $this->assertInLogs(
            'Notification-Priority: ' . Net_Growl::PRIORITY_HIGH,
            Project::MSG_DEBUG
        );
    }

    /**
     * Test a single notification with custom application and message icons
     *
     * @return void
     */
    public function testSingleCustomIconNotification()
    {
        $this->mockTask->setMessage('Custom Application and Icon message.');
        $this->mockTask->setAppicon('..\..\..\..\data\Help.ico');
        $this->mockTask->setIcon('..\..\..\..\data\warning.png');

        $this->executeTarget('test');
        $this->assertInLogs('Application-Icon:', Project::MSG_DEBUG);
        $this->assertInLogs('Notification-Icon:', Project::MSG_DEBUG);
    }
}
