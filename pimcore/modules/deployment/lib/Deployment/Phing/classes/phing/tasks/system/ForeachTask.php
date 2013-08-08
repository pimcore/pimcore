<?php
/*
 *  $Id: 7acb78ab792426fa7d4edec53496cff8da5923eb $
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

require_once 'phing/Task.php';
require_once 'phing/system/io/FileSystem.php';
include_once 'phing/mappers/FileNameMapper.php';
include_once 'phing/tasks/system/PhingTask.php';

/**
 * <foreach> task
 *
 * Task definition for the foreach task.  This task takes a list with
 * delimited values, and executes a target with set param.
 *
 * Usage:
 * <foreach list="values" target="targ" param="name" delimiter="|" />
 *
 * Attributes:
 * list      --> The list of values to process, with the delimiter character,
 *               indicated by the "delimiter" attribute, separating each value.
 * target    --> The target to call for each token, passing the token as the
 *               parameter with the name indicated by the "param" attribute.
 * param     --> The name of the parameter to pass the tokens in as to the
 *               target.
 * delimiter --> The delimiter string that separates the values in the "list"
 *               parameter.  The default is ",".
 *
 * @author    Jason Hines <jason@greenhell.com>
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Id$
 * @package   phing.tasks.system
 */
class ForeachTask extends Task {
    
    /** Delimter-separated list of values to process. */
    private $list;
    
    /** Name of parameter to pass to callee */
    private $param;
    
    /** Name of absolute path parameter to pass to callee */
    private $absparam;
    
    /** Delimiter that separates items in $list */
    private $delimiter = ',';
    
    /**
     * PhingCallTask that will be invoked w/ calleeTarget.
     * @var PhingCallTask
     */
    private $callee;
    
    /** Array of filesets */
    private $filesets = array();
    
    /** Instance of mapper **/
    private $mapperElement;
    
    /**
     * Array of filelists
     * @var array
     */
    private $filelists = array();
    
    /**
     * Target to execute.
     * @var string
     */
    private $calleeTarget;
    
    /**
     * Total number of files processed 
     * @var integer
     */
    private $total_files = 0;
    
    /**
     * Total number of directories processed 
     * @var integer
     */
    private $total_dirs  = 0;
    

    function init() {
        $this->callee = $this->project->createTask("phingcall");
        $this->callee->setOwningTarget($this->getOwningTarget());
        $this->callee->setTaskName($this->getTaskName());
        $this->callee->setLocation($this->getLocation());
        $this->callee->init();
    }

    /**
     * This method does the work.
     * @return void
     */   
    function main() {
        if ($this->list === null && count($this->filesets) == 0 && count($this->filelists) == 0) {
            throw new BuildException("Need either list, nested fileset or nested filelist to iterate through");
        }
        if ($this->param === null) {
            throw new BuildException("You must supply a property name to set on each iteration in param");
        }
        if ($this->calleeTarget === null) {
            throw new BuildException("You must supply a target to perform");
        }

        $callee = $this->callee;
        $callee->setTarget($this->calleeTarget);
        $callee->setInheritAll(true);
        $callee->setInheritRefs(true);
        $mapper = null;
        
        if ($this->mapperElement !== null) {
            $mapper = $this->mapperElement->getImplementation();
        }
        
        if (trim($this->list)) {
            $arr = explode($this->delimiter, $this->list);
        
            foreach ($arr as $value) {
                $value = trim($value);
                $premapped = '';
                if ($mapper !== null) {
                    $premapped = $value;
                    $value = $mapper->main($value);
                    if ($value === null) {
                        continue;
                    }
                    $value = array_shift($value);
                }
                $this->log("Setting param '$this->param' to value '$value'" . ($premapped ? " (mapped from '$premapped')" : ''), Project::MSG_VERBOSE);
                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->param);
                $prop->setValue($value);
                $callee->main();
            }
        }

        // filelists
        foreach ($this->filelists as $fl) {
            $srcFiles = $fl->getFiles($this->project);

            $this->process($callee, $fl->getDir($this->project), $srcFiles, array());
        }

        // filesets
        foreach ($this->filesets as $fs) {
            $ds       = $fs->getDirectoryScanner($this->project);
            $srcFiles = $ds->getIncludedFiles();
            $srcDirs  = $ds->getIncludedDirectories();

            $this->process($callee, $fs->getDir($this->project), $srcFiles, $srcDirs);
        }

        $this->log("Processed {$this->total_dirs} directories and {$this->total_files} files", Project::MSG_VERBOSE);
    }

    /**
     * Processes a list of files & directories 
     * 
     * @param Task      $callee
     * @param PhingFile $fromDir
     * @param array     $srcFiles
     * @param array     $srcDirs
     */
    protected function process(Task $callee, PhingFile $fromDir, $srcFiles, $srcDirs)
    {
        $mapper = null;
        
        if ($this->mapperElement !== null) {
            $mapper = $this->mapperElement->getImplementation();
        }
        
        $filecount = count($srcFiles);
        $this->total_files += $filecount;
        
        for ($j = 0; $j < $filecount; $j++) {
            $value = $srcFiles[$j];
            $premapped = "";

            if ($this->absparam) {
                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->absparam);
                $prop->setValue($fromDir . FileSystem::getFileSystem()->getSeparator() . $value);
            }
            
            if ($mapper !== null) {
                $premapped = $value;
                $value = $mapper->main($value);
                if ($value === null) {
                    continue;
                }
                $value = array_shift($value);
            }
            
            if ($this->param) {
                $this->log("Setting param '$this->param' to value '$value'" . ($premapped ? " (mapped from '$premapped')" : ''), Project::MSG_VERBOSE);
                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->param);
                $prop->setValue($value);
            }

            $callee->main();
        }

        $dircount = count($srcDirs);
        $this->total_dirs += $dircount;
        
        for ($j = 0; $j <  $dircount; $j++) {
            $value = $srcDirs[$j];
            $premapped = "";

            if ($this->absparam) {
                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->absparam);
                $prop->setValue($fromDir . FileSystem::getFileSystem()->getSeparator() . $value);
            }
            
            if ($mapper !== null) {
                $premapped = $value;
                $value = $mapper->main($value);
                if ($value === null) {
                    continue;
                }
                $value = array_shift($value);
            }
            
            if ($this->param) {
                $this->log("Setting param '$this->param' to value '$value'" . ($premapped ? " (mapped from '$premapped')" : ''), Project::MSG_VERBOSE);
                $prop = $callee->createProperty();
                $prop->setOverride(true);
                $prop->setName($this->param);
                $prop->setValue($value);
            }

            $callee->main();
        }
    }

    function setList($list) {
        $this->list = (string) $list;
    }

    function setTarget($target) {
        $this->calleeTarget = (string) $target;
    }

    function setParam($param) {
        $this->param = (string) $param;
    }

    function setAbsparam($absparam) {
        $this->absparam = (string) $absparam;
    }

    function setDelimiter($delimiter) {
        $this->delimiter = (string) $delimiter;
    }

    /**
     * Nested creator, adds a set of files (nested fileset attribute).
     */
    function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    /**
     * Nested creator, creates one Mapper for this task
     *
     * @access  public
     * @return  object  The created Mapper type object
     * @throws  BuildException
     */
    function createMapper() {
        if ($this->mapperElement !== null) {
            throw new BuildException("Cannot define more than one mapper", $this->location);
        }
        $this->mapperElement = new Mapper($this->project);
        return $this->mapperElement;
    }

    /**
     * @return Property
     */
    function createProperty() {
        return $this->callee->createProperty();
    }

    /**
     * Supports embedded <filelist> element.
     * @return FileList
     */
    public function createFileList() {
        $num = array_push($this->filelists, new FileList());
        return $this->filelists[$num-1];
    }
}
