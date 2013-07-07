<?php

/**
 *  $Id: 3e8eca5c91edb2e816d48e98778c5689a493d76b $
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
 * 
 * @package phing.util
 */
 
require_once 'phing/BuildFileTest.php';

/**
 * Testcases for phing.util.DirectoryScanner
 * 
 * Based on org.apache.tools.ant.DirectoryScannerTest
 * 
 * @see     http://svn.apache.org/viewvc/ant/core/trunk/src/tests/junit/org/apache/tools/ant/DirectoryScannerTest.java
 * @author  Michiel Rook <mrook@php.net>
 * @package phing.util
 */
class DirectoryScannerTest extends BuildFileTest
{
    private $_basedir = ""; 
     
    public function setUp()
    {
        $this->_basedir = PHING_TEST_BASE . "/etc/util/tmp";
        $this->configureProject(PHING_TEST_BASE . "/etc/util/directoryscanner.xml");
        $this->executeTarget("setup");
    }
    
    public function tearDown()
    {
        $this->executeTarget("cleanup");
    }

    public function test1()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha"));
        $ds->scan();

        $this->compareFiles($ds, array(), array("alpha"));
    }
    
    public function test2()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha/"));
        $ds->scan();

        $this->compareFiles(
            $ds, 
            array(
            	"alpha/beta/beta.xml",
            	"alpha/beta/gamma/gamma.xml"
            ), 
            array(
            	"alpha",
            	"alpha/beta",
                "alpha/beta/gamma"
            )
        );
    }
    
    public function test3()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->scan();

        $this->compareFiles(
            $ds, 
            array(
            	"alpha/beta/beta.xml",
            	"alpha/beta/gamma/gamma.xml"
            ), 
            array(
                "",
            	"alpha",
            	"alpha/beta",
                "alpha/beta/gamma"
            )
        );
    }
    
    public function testFullPathMatchesCaseSensitive()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha/beta/gamma/GAMMA.XML"));
        $ds->scan();

        $this->compareFiles($ds, array(), array());
    }
    
    public function testFullPathMatchesCaseInsensitive()
    {
        $ds = new DirectoryScanner();
        $ds->setCaseSensitive(false);
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha/beta/gamma/GAMMA.XML"));
        $ds->scan();

        $this->compareFiles($ds, array("alpha/beta/gamma/gamma.xml"), array());
    }
    
    public function test2ButCaseInsensitive()
    {
        $ds = new DirectoryScanner();
        $ds->setCaseSensitive(false);
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("ALPHA/"));
        $ds->scan();

        $this->compareFiles(
            $ds, 
            array(
            	"alpha/beta/beta.xml",
            	"alpha/beta/gamma/gamma.xml"
            ), 
            array(
            	"alpha",
            	"alpha/beta",
                "alpha/beta/gamma"
            )
        );
    }
    
    public function testExcludeOneFile()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array( "**/*.xml"));
        $ds->setExcludes(array("alpha/beta/b*xml"));
        $ds->scan();
        
        $this->compareFiles($ds, array("alpha/beta/gamma/gamma.xml"), array());
    }

    public function testExcludeHasPrecedence()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha/**"));
        $ds->setExcludes(array("alpha/**"));
        $ds->scan();
        
        $this->compareFiles($ds, array(), array());
    }

    public function testAlternateIncludeExclude()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setIncludes(array("alpha/**", "alpha/beta/gamma/**"));
        $ds->setExcludes(array("alpha/beta/**"));
        $ds->scan();
        
        $this->compareFiles($ds, array(), array("alpha"));
    }

    public function testAlternateExcludeInclude()
    {
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setExcludes(array("alpha/**", "alpha/beta/gamma/**"));
        $ds->setIncludes(array("alpha/beta/**"));
        $ds->scan();
        
        $this->compareFiles($ds, array(), array());
    }
    
    public function testChildrenOfExcludedDirectory()
    {
        $this->executeTarget("children-of-excluded-dir-setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setExcludes(array("alpha/**"));
        $ds->scan();
        
        $this->compareFiles($ds, array("delta/delta.xml"), array("", "delta"));
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir);
        $ds->setExcludes(array("alpha"));
        $ds->scan();
        
        $this->compareFiles($ds, 
            array(
            	"alpha/beta/beta.xml",
               "alpha/beta/gamma/gamma.xml",
                "delta/delta.xml"), 
            array(
            	"",
            	"alpha/beta",
            	"alpha/beta/gamma",
            	"delta")
        );
    }

    public function testAbsolute1()
    {
        $base = $this->getProject()->getBasedir();
        $tmpdir = substr($this->replaceSeparator($base->getAbsolutePath()) . "/tmp", $base->getPrefixLength());
        $prefix = substr($base->getAbsolutePath(), 0, $base->getPrefixLength());
        
        $this->executeTarget("extended-setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($prefix);
        $ds->setIncludes(array($tmpdir . "/**/*"));
        $ds->scan();
        
        $this->compareFiles($ds,
            array(
                $tmpdir . "/alpha/beta/beta.xml",
                $tmpdir . "/alpha/beta/gamma/gamma.xml",
                $tmpdir . "/delta/delta.xml"
            ),
            array(
                $tmpdir . "/alpha",
                $tmpdir . "/alpha/beta",
                $tmpdir . "/alpha/beta/gamma",
                $tmpdir . "/delta"
            )
        );
    }
    
    public function testAbsolute2()
    {
        $base = $this->getProject()->getBasedir();
        $prefix = substr($base->getAbsolutePath(), 0, $base->getPrefixLength());

        $this->executeTarget("setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($prefix);
        $ds->setIncludes(array("alpha/**", "alpha/beta/gamma/**"));
        $ds->scan();
        
        $this->compareFiles($ds, array(), array());
    }

    public function testAbsolute3()
    {
        $base = $this->getProject()->getBasedir();
        $tmpdir = substr($this->replaceSeparator($base->getAbsolutePath()) . "/tmp", $base->getPrefixLength());
        $prefix = substr($base->getAbsolutePath(), 0, $base->getPrefixLength());
                
        $this->executeTarget("extended-setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($prefix);
        $ds->setIncludes(array($tmpdir . "/**/*"));
        $ds->setExcludes(array("**/alpha", "**/delta/*"));
        $ds->scan();
        
        $this->compareFiles($ds,
            array(
                $tmpdir . "/alpha/beta/beta.xml",
                $tmpdir . "/alpha/beta/gamma/gamma.xml"
            ),
            array(
                $tmpdir . "/alpha/beta",
                $tmpdir . "/alpha/beta/gamma",
                $tmpdir . "/delta"
            )
        );
    }

    public function testAbsolute4()
    {
        $base = $this->getProject()->getBasedir();
        $tmpdir = substr($this->replaceSeparator($base->getAbsolutePath()) . "/tmp", $base->getPrefixLength());
        $prefix = substr($base->getAbsolutePath(), 0, $base->getPrefixLength());
        
        $this->executeTarget("extended-setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($prefix);
        $ds->setIncludes(array($tmpdir . "/alpha/beta/**/*", $tmpdir . "/delta/*"));
        $ds->setExcludes(array("**/beta.xml"));
        $ds->scan();
        
        $this->compareFiles($ds, 
            array(
                $tmpdir . "/alpha/beta/gamma/gamma.xml",
                $tmpdir . "/delta/delta.xml"
            ),
            array($tmpdir . "/alpha/beta/gamma")
        );
    }
    
    /**
     * Inspired by http://www.phing.info/trac/ticket/137
     */
    public function testMultipleExcludes()
    {
        $this->executeTarget("multiple-setup");
        
        $ds = new DirectoryScanner();
        $ds->setBasedir($this->_basedir . "/echo");
        $ds->setIncludes(array("**"));
        $ds->setExcludes(array("**/.gitignore", ".svn/", ".git/", "cache/", "build.xml", "a/a.xml"));
        $ds->scan();

        $this->compareFiles($ds, array("b/b.xml"), array("", "a", "b"));
    }
    
    protected function replaceSeparator($item)
    {
        $fs = FileSystem::getFileSystem();
        
        return str_replace($fs->getSeparator(), '/', $item); 
    }
    
    protected function compareFiles(DirectoryScanner $ds, $expectedFiles, $expectedDirectories)
    {
        $includedFiles = $ds->getIncludedFiles();
        $includedDirectories = $ds->getIncludedDirectories();
        
        if (count($includedFiles)) {
            $includedFiles = array_map(array($this, 'replaceSeparator'), $includedFiles);
            natsort($includedFiles);
            $includedFiles = array_values($includedFiles);
        }
        
        if (count($includedDirectories)) {
            $includedDirectories = array_map(array($this, 'replaceSeparator'), $includedDirectories);
            natsort($includedDirectories);
            $includedDirectories = array_values($includedDirectories);
        }
        
        $this->assertEquals($includedFiles, $expectedFiles);
        $this->assertEquals($includedDirectories, $expectedDirectories);
    }
}
