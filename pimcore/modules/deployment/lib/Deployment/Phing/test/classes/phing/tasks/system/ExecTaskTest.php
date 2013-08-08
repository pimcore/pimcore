<?php
/*
 *  $Id: 735579119ca62de3a0deed9a12371b354e63efcc $
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
 * Tests the Exec Task
 *
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: 735579119ca62de3a0deed9a12371b354e63efcc $
 * @package phing.tasks.system
 */
class ExecTaskTest extends BuildFileTest
{
   /**
    * Whether test is being run on windows
    * @var bool
    */
    protected $windows;

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/ExecTest.xml'
        );
        $this->windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    }

    protected function getTargetByName($name)
    {
        foreach ($this->project->getTargets() as $target) {
            if ($target->getName() == $name) {
                return $target;
            }
        }
        throw new Exception(sprintf('Target "%s" not found', $name));
    }

    protected function getTaskFromTarget($target, $taskname, $pos = 0)
    {
        $rchildren = new ReflectionProperty(get_class($target), 'children');
        $rchildren->setAccessible(true);
        $n = -1;
        foreach ($rchildren->getValue($target) as $child) {
            if ($child instanceof Task && ++$n == $pos) {
                return $child;
            }
        }
        throw new Exception(
            sprintf('%s #%d not found in task', $taskname, $pos)
        );
    }

    protected function getConfiguredTask($target, $task, $pos = 0)
    {
        $target = $this->getTargetByName($target);
        $task = $this->getTaskFromTarget($target, $task);
        $task->maybeConfigure();
        return $task;
    }

    protected function assertPropertyIsSetTo($property, $value, $propertyName = null)
    {
        $task = $this->getConfiguredTask(
            'testPropertySet' . ucfirst($property), 'ExecTask'
        );

        if ($propertyName === null) {
            $propertyName = $property;
        }
        $rprop = new ReflectionProperty('ExecTask', $propertyName);
        $rprop->setAccessible(true);
        $this->assertEquals($value, $rprop->getValue($task));
    }

    public function testPropertySetCommand()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('command', "echo 'foo'");
    }

    public function testPropertySetDir()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo(
            'dir',
            new PhingFile(
                realpath(dirname(__FILE__) . '/../../../../etc/tasks/system')
            )
        );
    }

    public function testPropertySetOs()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('os', "linux");
    }

    public function testPropertySetEscape()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('escape', true);
    }

    public function testPropertySetLogoutput()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('logoutput', true, 'logOutput');
    }

    public function testPropertySetPassthru()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('passthru', true);
    }

    public function testPropertySetSpawn()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('spawn', true);
    }

    public function testPropertySetReturnProperty()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('returnProperty', 'retval');
    }

    public function testPropertySetOutputProperty()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('outputProperty', 'outval');
    }

    public function testPropertySetCheckReturn()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('checkreturn', true);
    }

    public function testPropertySetOutput()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo(
            'output',
            new PhingFile(
                realpath(dirname(__FILE__) . '/../../../../etc/tasks/system')
                . '/outputfilename'
            )
        );
    }

    public function testPropertySetError()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo(
            'error',
            new PhingFile(
                realpath(dirname(__FILE__) . '/../../../../etc/tasks/system')
                . '/errorfilename'
            )
        );
    }

    public function testPropertySetLevelError()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('levelError', Project::MSG_ERR, 'logLevel');
    }

    public function testPropertySetLevelWarning()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('levelWarning', Project::MSG_WARN, 'logLevel');
    }

    public function testPropertySetLevelInfo()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('levelInfo', Project::MSG_INFO, 'logLevel');
    }

    public function testPropertySetLevelVerbose()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('levelVerbose', Project::MSG_VERBOSE, 'logLevel');
    }

    public function testPropertySetLevelDebug()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->assertPropertyIsSetTo('levelDebug', Project::MSG_DEBUG, 'logLevel');
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage Unknown log level "unknown"
     */
    public function testPropertySetLevelUnknown()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $this->getConfiguredTask('testPropertySetLevelUnknown', 'ExecTask');
    }


    public function testDoNotExecuteOnWrongOs()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('Not found in unknownos');
        $this->assertNotContains(
            'this should not be executed',
            $this->getOutput()
        );
    }

    public function testExecuteOnCorrectOs()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('this should be executed');
    }


    public function testFailOnNonExistingDir()
    {
        try {
            $this->executeTarget(__FUNCTION__);
            $this->fail('Expected BuildException was not thrown');
        } catch (BuildException $e) {
            $this->assertContains(
                str_replace('/', DIRECTORY_SEPARATOR, "'/this/dir/does/not/exist' is not a valid directory"),
                $e->getMessage()
            );
        }
    }


    public function testChangeToDir()
    {
        if ($this->windows) {
            $this->markTestSkipped("Windows does not have 'ls'");
        }
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('ExecTaskTest.php');
    }

    public function testCheckreturnTrue()
    {
        if ($this->windows) {
            $this->markTestSkipped("Windows does not have '/bin/true'");
        }
        $this->executeTarget(__FUNCTION__);
        $this->assertTrue(true);
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage Task exited with code 1
     */
    public function testCheckreturnFalse()
    {
        if ($this->windows) {
            $this->markTestSkipped("Windows does not have '/bin/false'");
        }
        $this->executeTarget(__FUNCTION__);
    }

    public function testOutputProperty()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('The output property\'s value is: "foo"');
    }

    public function testReturnProperty()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('The return property\'s value is: "1"');
    }

    public function testEscape()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs($this->windows ? 'foo  |  cat' : 'foo | cat');
    }

    public function testPassthru()
    {
        ob_start();
        $this->executeTarget(__FUNCTION__);
        $out = ob_get_clean();
        $this->assertEquals("foo", rtrim($out, " \r\n"));
        //foo should not be in logs, except for the logged command
        $this->assertInLogs('echo foo');
        $this->assertNotContains('foo', $this->logBuffer);
    }

    public function testOutput()
    {
        $file = tempnam(sys_get_temp_dir(), 'phing-exectest-');
        $this->project->setProperty('execTmpFile', $file);
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('outfoo', file_get_contents($file));
        unlink($file);
    }

    public function testError()
    {
        if ($this->windows) {
            $this->markTestSkipped("The script is unlikely to run on Windows");
        }
        $file = tempnam(sys_get_temp_dir(), 'phing-exectest-');
        $this->project->setProperty('execTmpFile', $file);
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('errfoo', file_get_contents($file));
        unlink($file);
    }

    public function testSpawn()
    {
        $start = time();
        $this->executeTarget(__FUNCTION__);
        $end = time();
        $this->assertLessThan(
            4, $end - $start,
            'Time between start and end should be lower than 4 seconds'
            . ' - otherwise it looks as spawning did not work'
        );
    }

    public function testNestedArg()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs($this->windows ? 'nested-arg "b  ar"' : 'nested-arg b  ar');
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage ExecTask: Either use "command" OR "executable"
     */
    public function testExecutableAndCommand()
    {
        $this->executeTarget(__FUNCTION__);
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage ExecTask: Please provide "command" OR "executable"
     */
    public function testMissingExecutableAndCommand()
    {
        $this->executeTarget(__FUNCTION__);
    }
    
    /**
     * Inspired by {@link http://www.phing.info/trac/ticket/833}
     */
    public function testEscapedArg()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals('outval', 'abc$b3!SB');
    }
}

?>