<?php

/*
 *  $Id: 73ffa39198e630ab35689d56f18f0f66d4413714 $
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
 * @author Hans Lellelid (Phing)
 * @author Conor MacNeill (Ant)
 * @package phing.tasks.system
 */
class PropertyTaskTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/property.xml");
    }
        

    public function test1() { 
        // should get no output at all
        $this->expectOutputAndError("test1", "", "");
    }

    public function test2() { 
        $this->expectLog("test2", "testprop1=aa, testprop3=xxyy, testprop4=aazz");
    }
    
    public function test3() {        
        try {
            $this->executeTarget("test3");
        } catch (BuildException $e) {
            $this->assertTrue(strpos($e->getMessage(), "was circularly defined") !== false, "Circular definition not detected - ");
            return;                     
        }
        $this->fail("Did not throw exception on circular exception");          
    }
    
    
    public function test4() { 
        $this->expectLog("test4", "http.url is http://localhost:999");
    }
    
    public function testPrefixSuccess() {
        $this->executeTarget("prefix.success");
        $this->assertEquals("80", $this->project->getProperty("server1.http.port"));
    }
    
    
    public function testPrefixFailure() {
       try {
            $this->executeTarget("prefix.fail");
        } catch (BuildException $e) {
            $this->assertTrue(strpos($e->getMessage(), "Prefix is only valid") !== false, "Prefix allowed on non-resource/file load - ");
            return;                     
        }
        $this->fail("Did not throw exception on invalid use of prefix");
    }
    
    public function testFilterChain()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertEquals("World", $this->project->getProperty("filterchain.test"));
    }
    
}
