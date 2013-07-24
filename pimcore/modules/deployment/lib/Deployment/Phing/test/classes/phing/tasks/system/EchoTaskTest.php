<?php

require_once 'phing/BuildFileTest.php';

/**
 * Tests the Echo Task
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @version $Id: 0133f9f16b867582418a33645b2ed15cda6a866b $
 * @package phing.tasks.system
 */
class EchoTaskTest extends BuildFileTest
{

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/EchoTest.xml'
        );
    }

    public function testPropertyMsg()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('This is a msg');
    }

    public function testPropertyMessage()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('This is a message');
    }

    public function testInlineText()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('This is a nested inline text message');
    }

    public function testFileset()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('EchoTest.xml');
    }

    public function testFilesetInline()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('foo');
        $this->assertInLogs('EchoTest.xml');
    }

    public function testFilesetMsg()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("foo\n");
        $this->assertInLogs('EchoTest.xml');
    }
}

?>