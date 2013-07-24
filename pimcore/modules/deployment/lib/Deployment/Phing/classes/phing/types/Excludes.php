<?php
/*
 *  $Id: 0c22f261d1984da235c6cf4993d1275c225a33ba $
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

include_once 'phing/types/DataType.php';
include_once 'phing/types/FileSet.php';
require_once 'phing/types/ExcludesNameEntry.php';

/**
 * Datatype which handles excluded files, classes and methods.
 *
 * @package phing.types
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: 0c22f261d1984da235c6cf4993d1275c225a33ba $
 * @since   2.4.6
 */
class Excludes extends DataType
{
    /**
     * The directory scanner for getting the excluded files
     *
     * @var DirectoryScanner
     */
    private $_directoryScanner = null;

    /**
     * Holds the excluded file patterns
     *
     * @var array
     */
    private $_files = array();

    /**
     * Holds the excluded classes
     *
     * @var array
     */
    private $_classes = array();

    /**
     * Holds the excluded methods
     *
     * @var array
     */
    private $_methods = array();

    /**
     * ctor
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->_directoryScanner = new DirectoryScanner();
        $this->_directoryScanner->setBasedir($project->getBasedir());
    }

    /**
     * Add a name entry on the exclude file list
     *
     * @return ExcludesNameEntry Reference to object
     */
    public function createFile()
    {
        return $this->_addFile($this->_files);
    }

    /**
     * Add a name entry on the exclude class list
     *
     * @return ExcludesNameEntry Reference to object
     */
    public function createClass()
    {
        return $this->_addClass($this->_classes);
    }

    /**
     * Add a name entry on the exclude method list
     *
     * @return ExcludesNameEntry Reference to object
     */
    public function createMethod()
    {
        return $this->_addMethod($this->_methods);
    }

    /**
     * Adds a file to the exclusion list
     *
     * @param FileSet $fileSet The FileSet into which the nameentry should be added
     *
     * @return ExcludesNameEntry Reference to the created ExcludesNameEntry instance
     */
    private function _addFile(&$fileList)
    {
        $file       = new ExcludesNameEntry();
        $fileList[] = $file;

        return $file;
    }

    /**
     * Adds a class to the exclusion list
     *
     * @return ExcludesNameEntry Reference to the created ExcludesNameEntry instance
     */
    private function _addClass(&$classList)
    {
        $excludedClass = new ExcludesNameEntry();
        $classList[]   = $excludedClass;

        return $excludedClass;
    }

    /**
     * Adds a method to the exclusion list
     *
     * @return ExcludesNameEntry Reference to the created ExcludesNameEntry instance
     */
    private function _addMethod(&$methodList)
    {
        $excludedMethod = new ExcludesNameEntry();
        $methodList[]   = $excludedMethod;

        return $excludedMethod;
    }

    /**
     * Returns the excluded files
     *
     * @return array
     */
    public function getExcludedFiles()
    {
        $includes = array();

        foreach ($this->_files as $file) {
            $includes[] = $file->getName();
        }

        $this->_directoryScanner->setIncludes($includes);
        $this->_directoryScanner->scan();

        $files    = $this->_directoryScanner->getIncludedFiles();
        $dir      = $this->_directoryScanner->getBasedir();
        $fileList = array();

        foreach ($files as $file) {
            $fileList[] = $dir . DIRECTORY_SEPARATOR . $file;
        }

        return $fileList;
    }

    /**
     * Returns the excluded class names
     *
     * @return array
     */
    public function getExcludedClasses()
    {
        $excludedClasses = array();

        foreach ($this->_classes as $excludedClass) {
            $excludedClasses[] = $excludedClass->getName();
        }

        return $excludedClasses;
    }

    /**
     * Returns the excluded method names
     *
     * @return array
     */
    public function getExcludedMethods()
    {
        $excludedMethods = array();

        foreach ($this->_methods as $excludedMethod) {
            $classAndMethod = explode('::', $excludedMethod->getName());
            $className      = $classAndMethod[0];
            $methodName     = $classAndMethod[1];

            $excludedMethods[$className][] = $methodName;
        }

        return $excludedMethods;
    }
}