<?php

require_once 'phing/BuildFileTest.php';

/**
 * Tests the Condition Task
 *
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: 0133f9f16b867582418a33645b2ed15cda6a866b $
 * @package phing.tasks.system
 */
class ConditionTaskTest extends BuildFileTest
{

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/ConditionTest.xml'
        );
    }

    public function testEquals()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertySet('isEquals');
    }

    public function testContains()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertySet('isContains');
    }

    public function testCustomCondition()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertySet('isCustom');
    }
}

