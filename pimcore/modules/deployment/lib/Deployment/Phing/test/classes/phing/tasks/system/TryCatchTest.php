<?php

require_once 'phing/BuildFileTest.php';

/**
 * Tests the Echo Task
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @version $Id: c0a9d45975c85f57c2c2cb28b13265d9dac1a645 $
 * @package phing.tasks.system
 */
class TryCatchTaskTest extends BuildFileTest
{

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/TryCatchTest.xml'
        );
    }

    public function testTryCatchFinally()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('In <catch>.');
        $this->assertInLogs('In <finally>.');
        $this->assertStringEndsWith('Tada!', $this->project->getProperty("prop." . __FUNCTION__));
    }
}

