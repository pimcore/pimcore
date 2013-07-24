<?php
/**
 *  $Id: 8fbff39b2ca68e97afd59d5dc6d5a37c3678624e $
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
require_once 'phing/tasks/ext/phpcpd/PHPCPDFormatterElement.php';

/**
 * Runs PHP Copy & Paste Detector. Checking PHP files for duplicated code.
 * Refactored original PhpCpdTask provided by
 * Timo Haberkern <timo.haberkern@fantastic-bits.de>
 *
 * @package phing.tasks.ext.phpcpd
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: 8fbff39b2ca68e97afd59d5dc6d5a37c3678624e $
 */
class PHPCPDTask extends Task
{
    /**
     * A php source code filename or directory
     *
     * @var PhingFile
     */
    protected $_file = null;

    /**
     * All fileset objects assigned to this task
     *
     * @var array<FileSet>
     */
    protected $_filesets = array();

    /**
     * Minimum number of identical lines.
     *
     * @var integer
     */
    protected $_minLines = 5;

    /**
     * Minimum number of identical tokens.
     *
     * @var integer
     */
    protected $_minTokens = 70;

    /**
     * List of valid file extensions for analyzed files.
     *
     * @var array
     */
    protected $_allowedFileExtensions = array('php');

    /**
     * List of exclude directory patterns.
     *
     * @var array
     */
    protected $_ignorePatterns = array('.git', '.svn', 'CVS', '.bzr', '.hg');

    /**
     * The format for the report
     *
     * @var string
     */
    protected $_format = 'default';

    /**
     * Formatter elements.
     *
     * @var array<PHPCPDFormatterElement>
     */
    protected $_formatters = array();

    /**
     * Set the input source file or directory.
     *
     * @param PhingFile $file The input source file or directory.
     *
     * @return void
     */
    public function setFile(PhingFile $file)
    {
        $this->_file = $file;
    }

    /**
     * Nested creator, adds a set of files (nested fileset attribute).
     *
     * @param FileSet $fs List of files to scan
     *
     * @return void
     */
    public function addFileSet(FileSet $fs)
    {
        $this->_filesets[] = $fs;
    }

    /**
     * Sets the minimum number of identical lines (default: 5).
     *
     * @param integer $minLines Minimum number of identical lines
     *
     * @return void
     */
    public function setMinLines($minLines)
    {
        $this->_minLines = $minLines;
    }

    /**
     * Sets the minimum number of identical tokens (default: 70).
     *
     * @param integer $minTokens Minimum number of identical tokens
     */
    public function setMinTokens($minTokens)
    {
        $this->_minTokens = $minTokens;
    }

    /**
     * Sets a list of filename extensions for valid php source code files.
     *
     * @param string $fileExtensions List of valid file extensions.
     *
     * @return void
     */
    public function setAllowedFileExtensions($fileExtensions)
    {
        $this->_allowedFileExtensions = array();

        $token = ' ,;';
        $ext   = strtok($fileExtensions, $token);

        while ($ext !== false) {
            $this->_allowedFileExtensions[] = $ext;
            $ext = strtok($token);
        }
    }

    /**
     * Sets a list of ignore patterns that is used to exclude directories from
     * the source analysis.
     *
     * @param string $ignorePatterns List of ignore patterns.
     *
     * @return void
     */
    public function setIgnorePatterns($ignorePatterns)
    {
        $this->_ignorePatterns = array();

        $token   = ' ,;';
        $pattern = strtok($ignorePatterns, $token);

        while ($pattern !== false) {
            $this->_ignorePatterns[] = $pattern;
            $pattern = strtok($token);
        }
    }

    /**
     * Sets the output format
     *
     * @param string $format Format of the report
     */
    public function setFormat($format)
    {
        $this->_format = $format;
    }

    /**
     * Create object for nested formatter element.
     *
     * @return PHPCPDFormatterElement
     */
    public function createFormatter()
    {
        $num = array_push(
            $this->_formatters,
            new PHPCPDFormatterElement($this)
        );
        return $this->_formatters[$num-1];
    }

    /**
     * Executes PHPCPD against PhingFile or a FileSet
     *
     * @throws BuildException - if the phpcpd classes can't be loaded.
     * @return void
     */
    public function main()
    {
        /**
         * Determine PHPCPD installation
         */
        $oldVersion = false;
        
        if (!@include_once('SebastianBergmann/PHPCPD/autoload.php')) {
            if (!@include_once('PHPCPD/Autoload.php')) {
                throw new BuildException(
                    'PHPCPDTask depends on PHPCPD being installed '
                    . 'and on include_path.',
                    $this->getLocation()
                );
            }
            
            $oldVersion = true;
        } else {
            if (version_compare(PHP_VERSION, '5.3.0') < 0) {
                throw new BuildException("The PHPCPD task now requires PHP 5.3+");
            }
            
            $oldVersion = false;
        }
        
        if (!isset($this->_file) and count($this->_filesets) == 0) {
            throw new BuildException(
                "Missing either a nested fileset or attribute 'file' set"
            );
        }

        if (count($this->_formatters) == 0) {
            // turn legacy format attribute into formatter
            $fmt = new PHPCPDFormatterElement($this);
            $fmt->setType($this->_format);
            $fmt->setUseFile(false);
            $this->_formatters[] = $fmt;
        }

        $this->validateFormatters();

        $filesToParse = array();

        if ($this->_file instanceof PhingFile) {
            $filesToParse[] = $this->_file->getPath();
        } else {
            // append any files in filesets
            foreach ($this->_filesets as $fs) {
                $files = $fs->getDirectoryScanner($this->project)
                            ->getIncludedFiles();

                foreach ($files as $filename) {
                     $f = new PhingFile($fs->getDir($this->project), $filename);
                     $filesToParse[] = $f->getAbsolutePath();
                }
            }
        }

        $this->log('Processing files...');
        
        if ($oldVersion) {
            $detectorClass = 'PHPCPD_Detector';
            $strategyClass = 'PHPCPD_Detector_Strategy_Default';
        } else {
            $detectorClass = '\\SebastianBergmann\\PHPCPD\\Detector\\Detector';
            $strategyClass = '\\SebastianBergmann\\PHPCPD\\Detector\\Strategy\\DefaultStrategy';
        }
        
        $detector = new $detectorClass(new $strategyClass);
        $clones   = $detector->copyPasteDetection(
            $filesToParse,
            $this->_minLines,
            $this->_minTokens
        );

        $this->log('Finished copy/paste detection');

        foreach ($this->_formatters as $fe) {
            $formatter = $fe->getFormatter();
            $formatter->processClones(
                $clones,
                $this->project,
                $fe->getUseFile(),
                $fe->getOutfile()
                );
        }
    }

    /**
     * Validates the available formatters
     *
     * @throws BuildException
     * @return void
     */
    protected function validateFormatters()
    {
        foreach ($this->_formatters as $fe) {
            if ($fe->getType() == '') {
                throw new BuildException(
                    "Formatter missing required 'type' attribute."
                );
            }

            if ($fe->getUsefile() && $fe->getOutfile() === null) {
                throw new BuildException(
                    "Formatter requires 'outfile' attribute "
                    . "when 'useFile' is true."
                );
            }
        }
    }
}