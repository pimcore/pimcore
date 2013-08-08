<?php
/**
 * $Id: 529f9b6ab9ced7b78871e3612cd8afce58261a6f $
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

require_once 'phing/tasks/ext/phpunit/formatter/PHPUnitResultFormatter.php';

/**
 * Prints plain text output of the test to a specified Writer.
 *
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: 529f9b6ab9ced7b78871e3612cd8afce58261a6f $
 * @package phing.tasks.ext.phpunit.formatter
 * @since 2.1.0
 */
class PlainPHPUnitResultFormatter extends PHPUnitResultFormatter
{
    private $inner = "";
    
    public function getExtension()
    {
        return ".txt";
    }
    
    public function getPreferredOutfile()
    {
        return "testresults";
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        parent::startTestSuite($suite);
        
        $this->inner = "";
    }
    
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($suite->getName() == 'AllTests')
        {
            return false;
        }
        
        $sb = "Testsuite: " . $suite->getName() . "\n";
        $sb.= "Tests run: " . $this->getRunCount();
        $sb.= ", Failures: " . $this->getFailureCount();
        $sb.= ", Errors: " . $this->getErrorCount();
        $sb.= ", Incomplete: " . $this->getIncompleteCount();
        $sb.= ", Skipped: " . $this->getSkippedCount();
        $sb.= ", Time elapsed: " . sprintf('%0.5f', $this->getElapsedTime()) . " s\n";

        if ($this->out != NULL)
        {
            $this->out->write($sb);
            $this->out->write($this->inner);
        }

        parent::endTestSuite($suite);
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        parent::addError($test, $e, $time);
        
        $this->formatError("ERROR", $test, $e);
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        parent::addFailure($test, $e, $time);
        $this->formatError("FAILED", $test, $e);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        parent::addIncompleteTest($test, $e, $time);
        
        $this->formatError("INCOMPLETE", $test);
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        parent::addSkippedTest($test, $e, $time);
        $this->formatError("SKIPPED", $test);
    }

    private function formatError($type, PHPUnit_Framework_Test $test, Exception $e = null)
    {
        if ($test != null)
        {
            $this->endTest($test, time());
        }
        
        $this->inner.= $test->getName() . " " . $type . "\n";
        
        if ($e !== null) {
            $this->inner.= $e->getMessage() . "\n";
            // $this->inner.= PHPUnit_Util_Filter::getFilteredStackTrace($e, true) . "\n";
        }
    }
    
    public function endTestRun()
    {
        parent::endTestRun();
        
        if ($this->out != NULL)
        {
            $this->out->close();
        }
    }
}

