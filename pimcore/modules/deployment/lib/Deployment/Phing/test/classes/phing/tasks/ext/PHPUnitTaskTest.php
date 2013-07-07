<?php

/*
 *  $Id: fd4db6412e8a84cee15c13c07f4629ffac58ef4b $
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
 * Unit tests for PHPUnit task
 *
 * @package phing.tasks.ext
 */
class PHPUnitTaskTest extends BuildFileTest { 
    protected $backupGlobals = FALSE;
        
    public function setUp()
    { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/phpunit/build.xml");
    }

    /**
     * Regression test for http://www.phing.info/trac/ticket/655
     * "PlainPHPUnitResultFormatter does not display errors if dataProvider was used"
     */
    public function testPlainFormatterDataProvider()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("Tests run: 2, Failures: 1, Errors: 0, Incomplete: 0, Skipped: 0, Time elapsed:");
    }

    /**    
     * Regression test for ticket http://www.phing.info/trac/ticket/363
     * "PHPUnit task fails with formatter type 'xml'"
     */
    public function testXmlFormatter() {
      $this->executeTarget(__FUNCTION__);
      $this->assertInLogs("<testcase name=\"testSayHello\" class=\"HelloWorldTest\"");
    }
    
    /**
     * Regression test for ticket http://www.phing.info/trac/ticket/893
     */
    public function testDoubleAutoloader()
    {
      $this->executeTarget(__FUNCTION__);
    }
}
