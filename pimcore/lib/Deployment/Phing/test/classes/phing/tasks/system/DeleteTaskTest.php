<?php
/*
 *  $Id: a79a9a60f4d4065fd941c2083f6f77e7f20aed5b $
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
 * Tests the Delete Task
 * 
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: a79a9a60f4d4065fd941c2083f6f77e7f20aed5b $
 * @package phing.tasks.system
 */
class DeleteTaskTest extends BuildFileTest 
{ 
    public function setUp() 
    { 
        $this->configureProject(PHING_TEST_BASE 
                              . "/etc/tasks/system/DeleteTaskTest.xml");
        $this->executeTarget("setup");
    }
    
    public function tearDown()
    {
        $this->executeTarget("clean");
    }

    public function testCopyDanglingSymlink()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $this->markTestSkipped("Dangling symlinks don't work on Windows");
        }

        $this->executeTarget("testDeleteDanglingSymlink");
        $this->assertInLogs("Deleting 1 files from");
    }
    
    public function testDeleteNonExistingDirectory()
    {
        $this->expectBuildExceptionContaining(__FUNCTION__, __FUNCTION__, 'does not exist or is not a directory');
    }

    public function testDeleteNonExistingFile()
    {
        $this->expectBuildExceptionContaining(__FUNCTION__, __FUNCTION__, 'Could not find file');
    }
}

