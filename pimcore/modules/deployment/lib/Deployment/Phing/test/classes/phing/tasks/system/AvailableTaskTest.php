<?php
/*
 *  $Id: df57e822e06c8ec96fee0e852590e4312c1aaace $
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
 * Tests the Available Task
 * 
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: df57e822e06c8ec96fee0e852590e4312c1aaace $
 * @package phing.tasks.system
 */
class AvailableTaskTest extends BuildFileTest 
{ 
    public function setUp() 
    { 
        $this->configureProject(PHING_TEST_BASE 
                              . "/etc/tasks/system/AvailableTaskTest.xml");
        $this->executeTarget("setup");
    }
    
    public function tearDown()
    {
        $this->executeTarget("clean");
    }

    public function testDanglingSymlink()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $this->markTestSkipped("Dangling symlinks don't work on Windows");
        }

        $this->executeTarget(__FUNCTION__);
        $this->assertNull($this->project->getProperty("prop." . __FUNCTION__));
    }

    public function testFileSymlink()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertEquals('true', $this->project->getProperty("prop." . __FUNCTION__));
    }

    public function testDirectorySymlink()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertEquals('true', $this->project->getProperty("prop." . __FUNCTION__));
    }

    public function testDirectorySymlinkBC()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertNull($this->project->getProperty("prop." . __FUNCTION__));
    }
}

