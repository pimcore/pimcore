<?php
/*
 *  $Id: 1131b9fdbf0386b52151e0c7b43c890b0dabbacd $
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

/**
 * PhpDocumentor2 Task (http://www.phpdoc.org)
 * Based on the DocBlox Task
 *
 * @author    Michiel Rook <mrook@php.net>
 * @version   $Id: 1131b9fdbf0386b52151e0c7b43c890b0dabbacd $
 * @since     2.4.10
 * @package   phing.tasks.ext.phpdoc
 */
class PhpDocumentor2Task extends Task
{
    /**
     * List of filesets
     * @var FileSet[]
     */
    private $filesets = array();
    
    /**
     * Destination/target directory
     * @var PhingFile
     */
    private $destDir = null;

    /**
     * name of the template to use
     * @var string
     */
    private $template = "responsive";
    
    /**
     * Title of the project
     * @var string
     */
    private $title = "";
    
    /**
     * Force phpDocumentor to be quiet
     * @var boolean
     */
    private $quiet = true;
    
    /**
     * Nested creator, adds a set of files (nested fileset attribute).
     * 
     * @return FileSet
     */
    public function createFileSet()
    {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }
    
    /**
     * Sets destination/target directory
     * @param PhingFile $destDir
     */
    public function setDestDir(PhingFile $destDir)
    {
        $this->destDir = $destDir;
    }

    /**
     * Convenience setter (@see setDestDir)
     * @param PhingFile $output
     */
    public function setOutput(PhingFile $output)
    {
        $this->destDir = $output;
    }

    /**
     * Sets the template to use
     * @param strings $template
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
    }
    
    /**
     * Sets the title of the project
     * @param strings $title
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;
    }
    
    /**
     * Forces phpDocumentor to be quiet
     * @param boolean $quiet
     */
    public function setQuiet($quiet)
    {
        $this->quiet = (boolean) $quiet;
    }
    
    /**
     * Task entry point
     * @see Task::main()
     */
    public function main()
    {
        if (empty($this->destDir)) {
            throw new BuildException("You must supply the 'destdir' attribute", $this->getLocation());
        }
        
        if (empty($this->filesets)) {
            throw new BuildException("You have not specified any files to include (<fileset>)", $this->getLocation());
        }
        
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new BuildException("The phpdocumentor2 task requires PHP 5.3+");
        }
        
        require_once 'phing/tasks/ext/phpdoc/PhpDocumentor2Wrapper.php';
        
        $wrapper = new PhpDocumentor2Wrapper();
        $wrapper->setProject($this->project);
        $wrapper->setFilesets($this->filesets);
        $wrapper->setDestDir($this->destDir);
        $wrapper->setTemplate($this->template);
        $wrapper->setTitle($this->title);
        $wrapper->run();
    }
}