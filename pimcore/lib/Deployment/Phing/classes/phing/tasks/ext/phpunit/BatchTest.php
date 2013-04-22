<?php
/**
 * $Id: 4067d915614ff7a864c31f19549bcf6a96c0f92d $
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

require_once 'phing/tasks/ext/phpunit/PHPUnitUtil.php';
require_once 'phing/types/FileSet.php';

/**
 * Scans a list of files given by the fileset attribute, extracts valid test cases
 *
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: 4067d915614ff7a864c31f19549bcf6a96c0f92d $
 * @package phing.tasks.ext.phpunit
 * @since 2.1.0
 */
class BatchTest
{
    /** the list of filesets containing the testcase filename rules */
    private $filesets = array();

    /** the reference to the project */
    private $project = NULL;

    /** the classpath to use with Phing::__import() calls */
    private $classpath = NULL;
    
    /** names of classes to exclude */
    private $excludeClasses = array();
    
    /** name of the batchtest/suite */
    protected $name = "Phing Batchtest";
    
    /**
     * Create a new batchtest instance
     *
     * @param Project the project it depends on.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
    
    /**
     * Sets the name of the batchtest/suite
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * Sets the classes to exclude
     */
    public function setExclude($exclude)
    {
        $this->excludeClasses = explode(" ", $exclude);
    }

    /**
     * Sets the classpath
     */
    public function setClasspath(Path $classpath)
    {
        if ($this->classpath === null)
        {
            $this->classpath = $classpath;
        }
        else
        {
            $this->classpath->append($classpath);
        }
    }

    /**
     * Creates a new Path object
     */
    public function createClasspath()
    {
        $this->classpath = new Path();
        return $this->classpath;
    }

    /**
     * Returns the classpath
     */
    public function getClasspath()
    {
        return $this->classpath;
    }

    /**
     * Add a new fileset containing the XML results to aggregate
     *
     * @param FileSet the new fileset containing XML results.
     */
    public function addFileSet(FileSet $fileset)
    {
        $this->filesets[] = $fileset;
    }

    /**
     * Iterate over all filesets and return the filename of all files.
     *
     * @return array an array of filenames
     */
    private function getFilenames()
    {
        $filenames = array();

        foreach ($this->filesets as $fileset)
        {
            $ds = $fileset->getDirectoryScanner($this->project);
            $ds->scan();

            $files = $ds->getIncludedFiles();

            foreach ($files as $file)
            {
                $filenames[] = $ds->getBaseDir() . "/" . $file;
            }
        }

        return $filenames;
    }
    
    /**
     * Checks wheter $input is a PHPUnit Test
     */
    private function isTestCase($input)
    {   
        return is_subclass_of($input, 'PHPUnit_Framework_TestCase') || is_subclass_of($input, 'PHPUnit_Framework_TestSuite');
    }
    
    /**
     * Filters an array of classes, removes all classes that are not test cases or test suites,
     * or classes that are declared abstract
     */
    private function filterTests($input)
    {
        $reflect = new ReflectionClass($input);
        
        return $this->isTestCase($input) && (!$reflect->isAbstract());
    }

    /**
     * Returns an array of test cases and test suites that are declared
     * by the files included by the filesets
     *
     * @return array an array of tests.
     */
    protected function elements()
    {
        $filenames = $this->getFilenames();
        
        $declaredClasses = array();     

        foreach ($filenames as $filename)
        {
            $definedClasses = PHPUnitUtil::getDefinedClasses($filename, $this->classpath);
            
            foreach($definedClasses as $definedClass) {
                $this->project->log("(PHPUnit) Adding $definedClass (from $filename) to tests.", Project::MSG_DEBUG);
            }
            
            $declaredClasses = array_merge($declaredClasses, $definedClasses);
        }
        
        $elements = array_filter($declaredClasses, array($this, "filterTests"));

        return $elements;
    }
    
    /**
     * Returns a testsuite containing all the tests in this batch
     *
     * @deprecated
     * @return PHPUnit_Framework_TestSuite
     */
    public function getTestSuite()
    {
        $suite = new PHPUnit_Framework_TestSuite($this->name);
        
        foreach ($this->elements() as $test)
        {
            $testClass = new $test();
            if (!($testClass instanceof PHPUnit_Framework_TestSuite))
            {
              $testClass = new ReflectionClass($test);
            }
            
            $suite->addTestSuite($testClass);
        }
        
        return $suite;
    }
    
    /**
     * Add the tests in this batchtest to a test suite
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function addToTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        foreach ($this->elements() as $element) {
            $testClass = new $element();
            if (!($testClass instanceof PHPUnit_Framework_TestSuite))
            {
                $testClass = new ReflectionClass($element);
            }
            $suite->addTestSuite($testClass);
        }
    }
    
}
