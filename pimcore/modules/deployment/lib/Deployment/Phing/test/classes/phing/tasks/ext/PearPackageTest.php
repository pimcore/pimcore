<?php
/*
 *  $Id: 191e89d7939e2abae020c20fbb9804d218b82426 $
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
 * Test cases for the pearpkg/pearpkg2 tasks
 *
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: 191e89d7939e2abae020c20fbb9804d218b82426 $
 * @package phing.tasks.ext
 */
class PearPackageTest extends BuildFileTest { 
    protected $backupGlobals = FALSE;
    
    private $savedErrorLevel;
        
    public function setUp() { 
        $GLOBALS['_PEAR_Common_file_roles'] = array('php','ext','test','doc','data','src','script');
        $this->savedErrorLevel = error_reporting();
        error_reporting(E_ERROR);
        $buildFile = PHING_TEST_BASE . "/etc/tasks/ext/pearpackage.xml";
        $this->configureProject($buildFile);
    }
    
    public function tearDown()
    {
        error_reporting($this->savedErrorLevel);
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/package.xml');
    }

    public function testRoleSet () {      
        $this->executeTarget("main");
        $content = file_get_contents(PHING_TEST_BASE . '/etc/tasks/ext/package.xml');
        $this->assertTrue(strpos($content, '<file role="script" baseinstalldir="phing" name="pear-phing.bat"/>') !== false);
    }
}
