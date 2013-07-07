<?php

/*
 *  $Id: 29581d44540a2376649704d4cb7524c14415f718 $
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
 * Unit tests for PhpLintTask
 *
 * @package phing.tasks.ext
 */
class PhpLintTaskTest extends BuildFileTest { 
        
    public function setUp()
    { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/phplint/build.xml");
    }

    public function testSyntaxOK()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("my_file_ok.php: No syntax errors detected");
    }

    public function testSyntaxError()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("Parse error: syntax error, unexpected");
    }

    /**
     * Regression test for ticket http://www.phing.info/trac/ticket/590
     */
    public function testDeprecated()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("Assigning the return value of new by reference is deprecated in");
    }

    public function testHaltOnFailure()
    {
        $this->expectBuildException(__FUNCTION__, " Syntax error(s) in PHP files: ./my_file.php=Parse error: syntax error, unexpected T_ENCAPSED_AND_WHITESPACE in ./my_file.php on line 2");
    }
}
