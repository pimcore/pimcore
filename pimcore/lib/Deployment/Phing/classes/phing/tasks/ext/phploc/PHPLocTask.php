<?php

/**
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
require_once 'phing/BuildException.php';

/**
 * Runs phploc a tool for quickly measuring the size of PHP projects.
 *
 * @package phing.tasks.ext.phploc
 * @author  Raphael Stolt <raphael.stolt@gmail.com>
 */
class PHPLocTask extends Task
{
    protected $suffixesToCheck = null;
    protected $acceptedReportTypes = null;
    protected $reportDirectory = null;
    protected $reportType = null;
    protected $countTests = null;
    protected $fileToCheck = null;
    protected $filesToCheck = null;
    protected $reportFileName = null;
    protected $fileSets = null;
    protected $oldVersion = false;
    
    public function init() {
        $this->suffixesToCheck = array('php');
        $this->acceptedReportTypes = array('cli', 'txt', 'xml', 'csv');
        $this->reportType = 'cli';
        $this->reportFileName = 'phploc-report';
        $this->fileSets = array();
        $this->filesToCheck = array();
        $this->countTests = false;
    }
    /**
     * @param string $suffixListOrSingleSuffix
     */
    public function setSuffixes($suffixListOrSingleSuffix) {
        if (stripos($suffixListOrSingleSuffix, ',')) {
            $suffixes = explode(',', $suffixListOrSingleSuffix);
            $this->suffixesToCheck = array_map('trim', $suffixes);
        } else {
            array_push($this->suffixesToCheck, trim($suffixListOrSingleSuffix));
        }
    }
    /**
     * @param PhingFile $file
     */
    public function setFile(PhingFile $file) {
        $this->fileToCheck = trim($file);
    }
    
    public function setCountTests($countTests) {
        $this->countTests = (bool) $countTests;
    }
    /**
     * @return array
     */
    public function createFileSet() {
        $num = array_push($this->fileSets, new FileSet());
        return $this->fileSets[$num - 1];
    }
    /**
     * @param string $type
     */
    public function setReportType($type) {
        $this->reportType = trim($type);
    }
    /**
     * @param string $name
     */
    public function setReportName($name) {
        $this->reportFileName = trim($name);
    }
    /**
     * @param string $directory
     */
    public function setReportDirectory($directory) {
        $this->reportDirectory = trim($directory);
    }
    
    public function main() {
        /**
         * Find PHPLoc
         */
        if (!@include_once('SebastianBergmann/PHPLOC/autoload.php')) {
            if (!@include_once('PHPLOC/Analyser.php')) {
                throw new BuildException(
                    'PHPLocTask depends on PHPLoc being installed and on include_path.',
                    $this->getLocation()
                );
            } else {
                $this->oldVersion = true;
            }
        }
        
        $this->_validateProperties();
        if (!is_null($this->reportDirectory) && !is_dir($this->reportDirectory)) {
            $reportOutputDir = new PhingFile($this->reportDirectory);
            $logMessage = "Report output directory doesn't exist, creating: " 
                . $reportOutputDir->getAbsolutePath() . '.';
            $this->log($logMessage);
            $reportOutputDir->mkdirs();
        }
        if ($this->reportType !== 'cli') {
            $this->reportFileName.= '.' . trim($this->reportType);
        }
        if (count($this->fileSets) > 0) {
            $project = $this->getProject();
            foreach ($this->fileSets as $fileSet) {
                $directoryScanner = $fileSet->getDirectoryScanner($project);
                $files = $directoryScanner->getIncludedFiles();
                $directory = $fileSet->getDir($this->project)->getPath();
                foreach ($files as $file) {
                    if ($this->isFileSuffixSet($file)) {
                        $this->filesToCheck[] = $directory . DIRECTORY_SEPARATOR 
                            . $file;
                    }
                }
            }
            $this->filesToCheck = array_unique($this->filesToCheck);
        }
        $this->runPhpLocCheck();
    }
    /**
     * @throws BuildException
     */
    private function _validateProperties() {
        if (!isset($this->fileToCheck) && count($this->fileSets) === 0) {
            $exceptionMessage = "Missing either a nested fileset or the "
                . "attribute 'file' set.";
            throw new BuildException($exceptionMessage);
        }
        if (count($this->suffixesToCheck) === 0) {
            throw new BuildException("No file suffix defined.");
        }
        if (is_null($this->reportType)) {
            throw new BuildException("No report type defined.");
        }
        if (!is_null($this->reportType) && 
            !in_array($this->reportType, $this->acceptedReportTypes)) {
            throw new BuildException("Unaccepted report type defined.");
        }
        if (!is_null($this->fileToCheck) && !file_exists($this->fileToCheck)) {
            throw new BuildException("File to check doesn't exist.");
        }
        if ($this->reportType !== 'cli' && is_null($this->reportDirectory)) {
            throw new BuildException("No report output directory defined.");
        }
        if (count($this->fileSets) > 0 && !is_null($this->fileToCheck)) {
            $exceptionMessage = "Either use a nested fileset or 'file' " 
                . "attribute; not both.";
            throw new BuildException($exceptionMessage);
        }
        if (!is_bool($this->countTests)) {
            $exceptionMessage = "'countTests' attribute has no boolean value";
            throw new BuildException($exceptionMessage);
        }
        if (!is_null($this->fileToCheck)) {
            if (!$this->isFileSuffixSet($file)) {
                $exceptionMessage = "Suffix of file to check is not defined in"
                    . " 'suffixes' attribute.";
                throw new BuildException($exceptionMessage);
            }
        }
    }
    /**
     * @param  string $filename
     * @return boolean
     */
    protected function isFileSuffixSet($filename) {
        $pathinfo = pathinfo($filename);
        $fileSuffix = $pathinfo['extension'];
        return in_array($fileSuffix, $this->suffixesToCheck);
    }
    
    protected function runPhpLocCheck() {
        $files = $this->getFilesToCheck();
        $result = $this->getCountForFiles($files); 

        if ($this->reportType === 'cli' || $this->reportType === 'txt') {
            if ($this->oldVersion) {
                require_once 'PHPLOC/TextUI/ResultPrinter/Text.php';
                $reportClass = 'PHPLOC_TextUI_ResultPrinter_Text';
            } else {
                $reportClass = '\\SebastianBergmann\\PHPLOC\\TextUI\\ResultPrinter';
            }
            $printer = new $reportClass();
            ob_start();
            $printer->printResult($result, $this->countTests); 
            $result = ob_get_contents(); 
            ob_end_clean();
            if ($this->reportType === 'txt') {
                file_put_contents($this->reportDirectory 
                    . DIRECTORY_SEPARATOR . $this->reportFileName, $result);
                $reportDir = new PhingFile($this->reportDirectory);
                $logMessage = "Writing report to: " 
                    . $reportDir->getAbsolutePath() . DIRECTORY_SEPARATOR 
                        . $this->reportFileName;
                $this->log($logMessage);
            } else {
                $this->log("\n" . $result);
            }
        } elseif ($this->reportType === 'xml' || $this->reportType === 'csv') {
            if ($this->oldVersion) {
                $printerClass = sprintf('PHPLOC_TextUI_ResultPrinter_%s', strtoupper($this->reportType)) ;
                $printerClassFile = str_replace('_', DIRECTORY_SEPARATOR, $printerClass) . '.php';
                require_once $printerClassFile;
            } else {
                $printerClass = '\\SebastianBergmann\\PHPLOC\\Log\\' . strtoupper($this->reportType);
            }
            
            $printer = new $printerClass();
            $reportDir = new PhingFile($this->reportDirectory);
            $logMessage = "Writing report to: " . $reportDir->getAbsolutePath()
                . DIRECTORY_SEPARATOR . $this->reportFileName;
            $this->log($logMessage);
            $printer->printResult($this->reportDirectory . DIRECTORY_SEPARATOR
                . $this->reportFileName, $result);
        }
    }
    /**
     * @return array
     */
    protected function getFilesToCheck() {
        if (count($this->filesToCheck) > 0) {
            $files = array();
            foreach ($this->filesToCheck as $file) {
                $files[] = new SPLFileInfo($file);
            }
        } elseif (!is_null($this->fileToCheck)) {
            $files = array(new SPLFileInfo($this->fileToCheck));
        }
        return $files;
    }
    /**
     * @param  array $files
     * @return array
     */
    protected function getCountForFiles(array $files) {
        $analyserClass = ($this->oldVersion ? 'PHPLOC_Analyser' : '\\SebastianBergmann\\PHPLOC\\Analyser');
        $analyser = new $analyserClass();
        
        return $analyser->countFiles($files, $this->countTests);
    }
}