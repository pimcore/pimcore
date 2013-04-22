<?php

/*
 *  $Id: 73e1e6026528033fadd8772cf5400c2f2a65e739 $
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
require_once 'PHPUnit/Framework/TestCase.php';
include_once 'phing/types/FileSet.php';

/**
 * Unit tests for AbstractFileSet.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @version $Id$
 * @package phing.types
 */
abstract class AbstractFileSetTest extends PHPUnit_Framework_TestCase {

    private $project;
    
    public function setUp() {
        $this->project = new Project();
        $this->project->setBasedir(PHING_TEST_BASE);
    }

    protected abstract function getInstance();

    protected final function getProject() {
        return $this->project;
    }

    public final function testEmptyElementIfIsReference() {
        $f = $this->getInstance();
        $f->setIncludes("**/*.php");
        try {
            $f->setRefid(new Reference("dummyref"));
            $this->fail("Can add reference to "
                 . $f->getDataTypeName()
                 . " with elements from setIncludes");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }

        $f = $this->getInstance();
        $f->createPatternSet();
        try {
            $f->setRefid(new Reference("dummyref"));
            $this->fail("Can add reference to "
                 . $f->getDataTypeName()
                 . " with nested patternset element.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when "
                         . "using refid", $be->getMessage());
        }

        $f = $this->getInstance();
        $f->createInclude();
        try {
            $f->setRefid(new Reference("dummyref"));
            $this->fail("Can add reference to "
                 . $f->getDataTypeName()
                 . " with nested include element.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }

        $f = $this->getInstance();
        $f->setRefid(new Reference("dummyref"));
        try {
            $f->setIncludes("**/*.java");
            $this->fail("Can set includes in "
                 . $f.getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }
        
        try {
            $f->setIncludesfile(new PhingFile("/a"));
            $this->fail("Can set includesfile in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }
 
        try {
            $f->setExcludes("**/*.java");
            $this->fail("Can set excludes in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }
 
        try {
            $f->setExcludesfile(new PhingFile("/a"));
            $this->fail("Can set excludesfile in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }
 
        try {
            $f->setDir($this->project->resolveFile("."));
            $this->fail("Can set dir in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify more than one attribute "
                         . "when using refid", $be->getMessage());
        }
 
        try {
            $f->createInclude();
            $this->fail("Can add nested include in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when using "
                         . "refid", $be->getMessage());
        }
        
        try {
            $f->createExclude();
            $this->fail("Can add nested exclude in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when using "
                         . "refid", $be->getMessage());
        }
        
        try {
            $f->createIncludesFile();
            $this->fail("Can add nested includesfile in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when using "
                         . "refid", $be->getMessage());
        }
        try {
            $f->createExcludesFile();
            $this->fail("Can add nested excludesfile in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when using "
                         . "refid", $be->getMessage());
        }
        try {
            $f->createPatternSet();
            $this->fail("Can add nested patternset in "
                 . $f->getDataTypeName()
                 . " that is a reference.");
        } catch (BuildException $be) {
            $this->assertEquals("You must not specify nested elements when using "
                         . "refid", $be->getMessage());
        }
        
    }   
    
    
}