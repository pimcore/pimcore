<?php

/*
 *  $Id: b951c60fa5142bd10bd8314bf336a7cb74b69234 $
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

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'phing/BuildFileTest.php';
include_once 'phing/types/Mapper.php';
include_once 'phing/Project.php';
include_once 'phing/types/Reference.php';

/**
 * Unit test for mappers.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Stefan Bodewig <stefan.bodewig@epost.de> (Ant)
 * @package phing.types
 */
class MapperTest extends PHPUnit_Framework_TestCase {

    private $project;

    public function setUp() {
        $this->project = new Project();                    
        $this->project->setBasedir(dirname(__FILE__));
    }

    public function testEmptyElementIfIsReference() {
        $m = new Mapper($this->project);
        $m->setFrom("*.java");
        try {
            $m->setRefid(new Reference("dummyref"));
            $this->fail("Can add reference to Mapper with from attribute set");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute when using refid", $be->getMessage());
        }

        $m = new Mapper($this->project);
        $m->setRefid(new Reference("dummyref"));
        try {
            $m->setFrom("*.java");
            $this->fail("Can set from in Mapper that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute when using refid", $be->getMessage());
        }

        $m = new Mapper($this->project);
        $m->setRefid(new Reference("dummyref"));
        try {
            $m->setTo("*.java");
            $this->fail("Can set to in Mapper that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute when using refid", $be->getMessage());
        }
        try {
            $m = new Mapper($this->project);
            $m->setRefid(new Reference("dummyref"));
            $m->setType("glob");
            $this->fail("Can set type in Mapper that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute when using refid", $be->getMessage());
        }
    }

    public function testCircularReferenceCheck() {
        $m = new Mapper($this->project);
        $this->project->addReference("dummy", $m);
        $m->setRefid(new Reference("dummy"));
        try {
            $m->getImplementation();
            $this->fail("Can make Mapper a Reference to itself.");
        } catch (BuildException $be) {
            $this->assertEquals("This data type contains a circular reference.", $be->getMessage());
        }

        // dummy1 --> dummy2 --> dummy3 --> dummy1
        $m1 = new Mapper($this->project);
        $this->project->addReference("dummy1", $m1);
        $m1->setRefid(new Reference("dummy2"));
        $m2 = new Mapper($this->project);
        $this->project->addReference("dummy2", $m2);
        $m2->setRefid(new Reference("dummy3"));
        $m3 = new Mapper($this->project);
        $this->project->addReference("dummy3", $m3);
        $m3->setRefid(new Reference("dummy1"));
        try {
            $m1->getImplementation();
            $this->fail("Can make circular reference.");
        } catch (BuildException $be) {
            $this->assertEquals("This data type contains a circular reference.", $be->getMessage());
        }

        // dummy1 --> dummy2 --> dummy3 
        // (which holds a glob mapper from "*.java" to "*.class"
        $m1 = new Mapper($this->project);
        $this->project->addReference("dummy1", $m1);
        $m1->setRefid(new Reference("dummy2"));
        $m2 = new Mapper($this->project);
        $this->project->addReference("dummy2", $m2);
        $m2->setRefid(new Reference("dummy3"));
        $m3 = new Mapper($this->project);
        $this->project->addReference("dummy3", $m3);
        
        $m3->setType("glob");
        $m3->setFrom("*.java");
        $m3->setTo("*.class");
                
        $fmm = $m1->getImplementation();
        $this->assertTrue($fmm instanceof GlobMapper, "Should be instance of GlobMapper");
        $result = $fmm->main("a.java");
        $this->assertEquals(1, count($result));
        $this->assertEquals("a.class", $result[0]);
    }

    public function testCopyTaskWithTwoFilesets() {
        $t = new TaskdefForCopyTest("test1");
        try {
            $t->setUp();
            $t->test1();
            $t->tearDown();
        } catch(Exception $e) {
            $t->tearDown();
            throw $e;
        }        
    }
    
}


/**
 * @package phing.mappers
 */
class TaskdefForCopyTest extends BuildFileTest {

    public function setUp() {
        $this->configureProject(PHING_TEST_BASE . "/etc/types/mapper.xml");
    }

    public function tearDown() {
        $this->executeTarget("cleanup");
    }

    public function test1() { 
        $this->executeTarget("test1");
    }
}