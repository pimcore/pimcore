<?php
/*
 *  $Id: 05cc97815c28aeefe9ae9013bd7be441086a7c36 $
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
 * Tests the LoadFile Task
 * 
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: 05cc97815c28aeefe9ae9013bd7be441086a7c36 $
 * @package phing.tasks.system
 */
class LoadFileTaskTest extends BuildFileTest 
{ 
    public function setUp() 
    { 
        $this->configureProject(PHING_TEST_BASE 
                              . "/etc/tasks/system/LoadFileTest.xml");
    }

    public function testVersionPropertySet()
    {
      $this->executeTarget("test-version");
      $this->assertInLogs('Property ${version} => 1.0.1');
    }
    
    public function testFilterChain()
    {
        $this->executeTarget("test-filterchain");
        $this->assertInLogs('<property>filterchain-test-123</property');
    }
}

