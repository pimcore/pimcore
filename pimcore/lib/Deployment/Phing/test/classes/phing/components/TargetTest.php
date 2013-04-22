<?php

/*
 *  $Id: 1fb73d9f9695e45d8de5c8f4d4b1eebd747894b9 $
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

/**
 * UTs for Target component
 *
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @author Daniel Holmes
 * @package phing.system
 */
class TargetTest extends BuildFileTest
{
    /** @var Target */
    private $target;
    
    public function setUp()
    { 
        $this->configureProject(PHING_TEST_BASE 
                              . "/etc/components/Target/Target.xml");
        
        $this->target = new Target();
        $this->target->setProject($this->project);
        $this->target->setName('MyTarget');
    }

    public function testHiddenTargets()
    {
        $phingExecutable = '"' . PHING_TEST_BASE . '/../bin/phing"';
        $buildFile = '"' . PHING_TEST_BASE . '/etc/components/Target/HiddenTargets.xml"';
        $cmd = $phingExecutable . ' -l -f ' . $buildFile;
        exec($cmd, $out);
        $out = implode("\n", $out);
        $offset = strpos($out, 'Subtargets:');
        $this->assertFalse(strpos($out, 'HideInListTarget', $offset));
        $this->assertTrue(strpos($out, 'ShowInListTarget', $offset) !== false);
    }

    /**
     * @dataProvider setDependsValidDataProvider
     * @param array $expectedDepends
     * @param string $depends
     */
    public function testSetDependsValid(array $expectedDepends, $depends)
    {
        $this->target->setDepends($depends);
        
        $this->assertEquals($expectedDepends, $this->target->getDependencies());
    }
    
    public function setDependsValidDataProvider()
    {
        return array(
            array(array('target1'), 'target1'),
            array(array('target1', 'target2'), 'target1,target2')
        );
    }
    
    /**
     * @dataProvider setDependsInvalidDataProvider
     * @param string $depends
     */
    public function testSetDependsInvalid($depends)
    {
        $this->setExpectedException('BuildException', 
            'Syntax Error: Depend attribute for target MyTarget is malformed.');
        
        $this->target->setDepends($depends);
    }
    
    public function setDependsInvalidDataProvider()
    {
        return array(
            array(''),
            array('target1,')
        );
    }
    
    public function testGetTasksReturnsCorrectTasks()
    {
        $task = new EchoTask();
        $task->setMessage('Hello World');
        $this->target->addTask($task);
        $this->target->addDataType('dataType');
        
        $tasks = $this->target->getTasks();
        
        $this->assertEquals(array($task), $tasks);
    }
    
    public function testGetTasksClonesTasks()
    {
        $task = new EchoTask();
        $task->setMessage('Hello World');
        $this->target->addTask($task);
        
        $tasks = $this->target->getTasks();
        
        $this->assertNotSame($task, $tasks[0]);
    }
    
    public function testMainAppliesConfigurables()
    {
        $configurable = $this->getMockBuilder('RuntimeConfigurable')
                             ->disableOriginalConstructor()
                             ->getMock();
        $configurable->expects($this->once())->method('maybeConfigure')->with($this->project);
        $this->target->addDataType($configurable);
        
        $this->target->main();
    }
    
    public function testMainFalseIfDoesntApplyConfigurable()
    {
        $this->project->setProperty('ifProperty', null);
        $this->target->setIf('ifProperty');
        
        $configurable = $this->getMockBuilder('RuntimeConfigurable')
                             ->disableOriginalConstructor()
                             ->getMock();
        $configurable->expects($this->never())->method('maybeConfigure');
        $this->target->addDataType($configurable);
        
        $this->target->main();
    }
    
    public function testMainTrueUnlessDoesntApplyConfigurable()
    {
        $this->project->setProperty('unlessProperty', 'someValue');
        $this->target->setUnless('unlessProperty');
        
        $configurable = $this->getMockBuilder('RuntimeConfigurable')
                             ->disableOriginalConstructor()
                             ->getMock();
        $configurable->expects($this->never())->method('maybeConfigure');
        $this->target->addDataType($configurable);
        
        $this->target->main();
    }
    
    public function testMainPerformsTasks()
    {
        $task = $this->getMock('Task');
        $task->expects($this->once())->method('perform');
        $this->target->addTask($task);
        
        $this->target->main();
    }
    
    public function testMainFalseIfDoesntPerformTasks()
    {
        $this->project->setProperty('ifProperty', null);
        $this->target->setIf('ifProperty');
        
        $task = $this->getMock('Task');
        $task->expects($this->never())->method('perform');
        $this->target->addTask($task);
        
        $this->target->main();
    }
    
    public function testMainTrueUnlessDoesntPerformTasks()
    {
        $this->project->setProperty('unlessProperty', 'someValue');
        $this->target->setUnless('unlessProperty');
        
        $task = $this->getMock('Task');
        $task->expects($this->never())->method('perform');
        $this->target->addTask($task);
        
        $this->target->main();
    }
}
