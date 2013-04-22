<?php
/*
 *  $Id: 9057464ff65111ebba5b7d12cad8349e152ecc1f $
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

/**
 * Wrapper around PhpDocumentor2 (so we retain
 * PHP 5.2 compatibility in the main task)
 *
 * @author    Michiel Rook <mrook@php.net>
 * @version   $Id: 9057464ff65111ebba5b7d12cad8349e152ecc1f $
 * @since     2.4.10
 * @package   phing.tasks.ext.phpdoc
 */
class PhpDocumentor2Wrapper
{
    /**
     * Phing project instance
     * @var Project
     */
    private $project = null;
    
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
     * @todo Make this work again
     * @var boolean
     */
    private $quiet = true;
    
    /**
     * Path to the phpDocumentor 2 source
     * @var string
     */
    private $phpDocumentorPath = "";
    
    /**
     * Sets project instance
     *
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }
    
    /**
     * Sets filesets array
     * 
     * @param FileSet[] $filesets
     */
    public function setFilesets($filesets)
    {
        $this->filesets = $filesets;
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
     * Finds and initializes the phpDocumentor installation
     */
    private function initializePhpDocumentor()
    {
        $phpDocumentorPath = null;
        
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            $testPhpDocumentorPath = $path . DIRECTORY_SEPARATOR . 'phpDocumentor' . DIRECTORY_SEPARATOR . 'src';

            if (file_exists($testPhpDocumentorPath)) {
                $phpDocumentorPath = $testPhpDocumentorPath;
            }
        }

        if (empty($phpDocumentorPath)) {
            throw new BuildException("Please make sure PhpDocumentor 2 is installed and on the include_path.", $this->getLocation());
        }
        
        set_include_path($phpDocumentorPath . PATH_SEPARATOR . get_include_path());
        
        require_once $phpDocumentorPath . '/phpDocumentor/Bootstrap.php';
            
        \phpDocumentor\Bootstrap::createInstance()->initialize();
        
        $this->phpDocumentorPath = $phpDocumentorPath;
    }
    
    /**
     * Build a list of files (from the fileset elements)
     * and call the phpDocumentor parser
     *
     * @return string
     */
    private function parseFiles()
    {
        $parser = new \phpDocumentor\Parser\Parser();
        $parser->setTitle($this->title);
        
        $paths = array();
        
        // filesets
        foreach ($this->filesets as $fs) {
            $ds    = $fs->getDirectoryScanner($this->project);
            $dir   = $fs->getDir($this->project);
            $srcFiles = $ds->getIncludedFiles();
            
            foreach ($srcFiles as $file) {
                $paths[] = $dir . FileSystem::getFileSystem()->getSeparator() . $file;
            }
        }
        
        $this->project->log("Will parse " . count($paths) . " file(s)", Project::MSG_VERBOSE);
        
        $files = new phpDocumentor\Fileset\Collection();
        $files->addFiles($paths);
        
        $parser->setPath($files->getProjectRoot());
        
        return $parser->parseFiles($files);
    }

    /**
     * Runs phpDocumentor 2 
     */
    public function run()
    {
        $this->initializePhpDocumentor();
        
        $xml = $this->parseFiles();
        
        $this->project->log("Transforming...", Project::MSG_VERBOSE);
        
        $transformer = new phpDocumentor\Transformer\Transformer();
        $transformer->setTemplatesPath($this->phpDocumentorPath . '/../data/templates');
        $transformer->setTemplates($this->template);
        $transformer->setSource($xml);
        $transformer->setTarget($this->destDir->getAbsolutePath());
        $transformer->execute();
    }
}