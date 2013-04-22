<?php
/**
 * $Id: d7e7e397e81588c3eafcb9e758666fec0fa166f5 $
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

require_once 'phing/tasks/ext/simpletest/SimpleTestResultFormatter.php';

/**
 * Prints plain text output of the test to a specified Writer.
 *
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: d7e7e397e81588c3eafcb9e758666fec0fa166f5 $
 * @package phing.tasks.ext.simpletest
 * @since 2.2.0
 */
class SimpleTestDebugResultFormatter extends SimpleTestResultFormatter
{
    protected $current_case = "";
    protected $current_test = "";
    private $failingTests = array();

    function printFailingTests()  {
        foreach ($this->failingTests as $test) {
            $this->out->write($test . "\n");
        }
    }

    function paintCaseStart($test_name)
    {
        parent::paintCaseStart($test_name);
        $this->paint( "Testsuite: $test_name\n");
    $this->current_case = $test_name;
    }

  function paintMethodStart($test_name)
    {
    parent::paintMethodStart($test_name);
    $this->current_test = $test_name;
    //$msg = "{$this->current_case} :: $test_name\n";
    $msg = "    TestCase: $test_name";
    $this->paint($msg);
  }

  function paint($msg) {
    if ($this->out == null ) {
      print $msg;
    } else {
      $this->out->write($msg);
    }
  }

  function paintMethodEnd($test_name) {
    parent::paintMethodEnd($test_name);
    $this->paint("\n");
  }

  function paintCaseEnd($test_name)
  {
    parent::paintCaseEnd($test_name);
    $this->current_case = "";
    /* Only count suites where more than one test was run */

    if ($this->getRunCount() && false)
    {
      $sb = "";
      $sb.= "Tests run: " . $this->getRunCount();
      $sb.= ", Failures: " . $this->getFailureCount();
      $sb.= ", Errors: " . $this->getErrorCount();
      $sb.= ", Time elapsed: " . $this->getElapsedTime();
      $sb.= " sec\n";
      $this->paint($sb);
    }

  }

  function paintError($message)
  {
    parent::paintError($message);
    $this->formatError("ERROR", $message);
    $this->failingTests[] = $this->current_case . "->" . $this->current_test;
  }

  function paintFail($message)
  {
    parent::paintFail($message);
    $this->formatError("FAILED", $message);
    $this->failingTests[] = $this->current_case . "->" . $this->current_test;
  }
  function paintException($message)
  {
    parent::paintException($message);
    $this->failingTests[] = $this->current_case . "->" . $this->current_test;
    $this->formatError("Exception", $message);
  }



  private function formatError($type, $message)
  {
    $this->paint("ERROR: $type: $message");
  }

}
