<?php
/*
 *  $Id: 2194d6e6307b4c64d60ec3d83975583fcaca5c03 $
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
 * A PHP code sniffer task. Checking the style of one or more PHP source files.
 *
 * @author  Dirk Thomas <dirk.thomas@4wdmedia.de>
 * @version $Id: 2194d6e6307b4c64d60ec3d83975583fcaca5c03 $
 * @package phing.tasks.ext
 */
class PhpCodeSnifferTask extends Task {

    protected $file;    // the source file (from xml attribute)
    protected $filesets = array(); // all fileset objects assigned to this task

    // parameters for php code sniffer
    protected $standard = 'Generic';
    protected $sniffs = array();
    protected $showWarnings = true;
    protected $showSources = false;
    protected $reportWidth = 80;
    protected $verbosity = 0;
    protected $tabWidth = 0;
    protected $allowedFileExtensions = array('php');
    protected $ignorePatterns = false;
    protected $noSubdirectories = false;
    protected $configData = array();
    protected $encoding = 'iso-8859-1';

    // parameters to customize output
    protected $showSniffs = false;
    protected $format = 'default';
    protected $formatters = array();

    /**
     * Holds the type of the doc generator
     *
     * @var string
     */
    protected $docGenerator = '';

    /**
     * Holds the outfile for the documentation
     *
     * @var PhingFile
     */
    protected $docFile = null;

    private $haltonerror = false;
    private $haltonwarning = false;
    private $skipversioncheck = false;

    /**
     * Load the necessary environment for running PHP_CodeSniffer.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * File to be performed syntax check on
     * @param PhingFile $file
     */
    public function setFile(PhingFile $file) {
        $this->file = $file;
    }

    /**
     * Nested creator, creates a FileSet for this task
     *
     * @return FileSet The created fileset object
     */
    public function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    /**
     * Sets the coding standard to test for
     *
     * @param string $standard The coding standard
     *
     * @return void
     */
    public function setStandard($standard)
    {
        $this->standard = $standard;
    }

    /**
     * Sets the sniffs which the standard should be restricted to
     * @param string $sniffs
     */
    public function setSniffs($sniffs)
    {
        $token = ' ,;';
        $sniff = strtok($sniffs, $token);
        while ($sniff !== false) {
            $this->sniffs[] = $sniff;
            $sniff = strtok($token);
        }
    }

    /**
     * Sets the type of the doc generator
     *
     * @param string $generator HTML or Text
     *
     * @return void
     */
    public function setDocGenerator($generator)
    {
        $this->docGenerator = $generator;
    }

    /**
     * Sets the outfile for the documentation
     *
     * @param PhingFile $file The outfile for the doc
     *
     * @return void
     */
    public function setDocFile(PhingFile $file)
    {
        $this->docFile = $file;
    }

    /**
     * Sets the flag if warnings should be shown
     * @param boolean $show
     */
    public function setShowWarnings($show)
    {
        $this->showWarnings = StringHelper::booleanValue($show);
    }

    /**
     * Sets the flag if sources should be shown
     *
     * @param boolean $show Whether to show sources or not
     *
     * @return void
     */
    public function setShowSources($show)
    {
        $this->showSources = StringHelper::booleanValue($show);
    }

    /**
     * Sets the width of the report
     *
     * @param int $width How wide the screen reports should be.
     *
     * @return void
     */
    public function setReportWidth($width)
    {
        $this->reportWidth = (int) $width;
    }

    /**
     * Sets the verbosity level
     * @param int $level
     */
    public function setVerbosity($level)
    {
        $this->verbosity = (int)$level;
    }

    /**
     * Sets the tab width to replace tabs with spaces
     * @param int $width
     */
    public function setTabWidth($width)
    {
        $this->tabWidth = (int)$width;
    }

    /**
     * Sets file encoding
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * Sets the allowed file extensions when using directories instead of specific files
     * @param array $extensions
     */
    public function setAllowedFileExtensions($extensions)
    {
        $this->allowedFileExtensions = array();
        $token = ' ,;';
        $ext = strtok($extensions, $token);
        while ($ext !== false) {
            $this->allowedFileExtensions[] = $ext;
            $ext = strtok($token);
        }
    }

    /**
     * Sets the ignore patterns to skip files when using directories instead of specific files
     * @param array $extensions
     */
    public function setIgnorePatterns($patterns)
    {
        $this->ignorePatterns = array();
        $token = ' ,;';
        $pattern = strtok($patterns, $token);
        while ($pattern !== false) {
            $this->ignorePatterns[] = $pattern;
            $pattern = strtok($token);
        }
    }

    /**
     * Sets the flag if subdirectories should be skipped
     * @param boolean $subdirectories
     */
    public function setNoSubdirectories($subdirectories)
    {
        $this->noSubdirectories = StringHelper::booleanValue($subdirectories);
    }

    /**
     * Creates a config parameter for this task
     *
     * @return Parameter The created parameter
     */
    public function createConfig() {
        $num = array_push($this->configData, new Parameter());
        return $this->configData[$num-1];
    }

    /**
     * Sets the flag if the used sniffs should be listed
     * @param boolean $show
     */
    public function setShowSniffs($show)
    {
        $this->showSniffs = StringHelper::booleanValue($show);
    }

    /**
     * Sets the output format
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Create object for nested formatter element.
     * @return CodeSniffer_FormatterElement
     */
    public function createFormatter () {
        $num = array_push($this->formatters,
        new PhpCodeSnifferTask_FormatterElement());
        return $this->formatters[$num-1];
    }

    /**
     * Sets the haltonerror flag
     * @param boolean $value
     */
    public function setHaltonerror($value)
    {
        $this->haltonerror = $value;
    }

    /**
     * Sets the haltonwarning flag
     * @param boolean $value
     */
    public function setHaltonwarning($value)
    {
        $this->haltonwarning = $value;
    }

    /**
     * Sets the skipversioncheck flag
     * @param boolean $value
     */
    public function setSkipVersionCheck($value)
    {
        $this->skipversioncheck = $value;
    }

    /**
     * Executes PHP code sniffer against PhingFile or a FileSet
     */
    public function main() {
        if (!class_exists('PHP_CodeSniffer')) {
            @include_once 'PHP/CodeSniffer.php';

            if (!class_exists('PHP_CodeSniffer')) {
                throw new BuildException("This task requires the PHP_CodeSniffer package installed and available on the include path", $this->getLocation());
            }
        }

        /**
         * Determine PHP_CodeSniffer version number
         */
        if (!$this->skipversioncheck) {
            preg_match('/\d\.\d\.\d/', shell_exec('phpcs --version'), $version);

            if (version_compare($version[0], '1.2.2') < 0) {
                throw new BuildException(
                    'PhpCodeSnifferTask requires PHP_CodeSniffer version >= 1.2.2',
                    $this->getLocation()
                );
            }
        }

        if(!isset($this->file) and count($this->filesets) == 0) {
            throw new BuildException("Missing either a nested fileset or attribute 'file' set");
        }

        if (PHP_CodeSniffer::isInstalledStandard($this->standard) === false) {
            // They didn't select a valid coding standard, so help them
            // out by letting them know which standards are installed.
            $installedStandards = PHP_CodeSniffer::getInstalledStandards();
            $numStandards       = count($installedStandards);
            $errMsg             = '';

            if ($numStandards === 0) {
                $errMsg = 'No coding standards are installed.';
            } else {
                $lastStandard = array_pop($installedStandards);

                if ($numStandards === 1) {
                    $errMsg = 'The only coding standard installed is ' . $lastStandard;
                } else {
                    $standardList  = implode(', ', $installedStandards);
                    $standardList .= ' and ' . $lastStandard;
                    $errMsg = 'The installed coding standards are ' . $standardList;
                }
            }

            throw new BuildException(
                'ERROR: the "' . $this->standard . '" coding standard is not installed. ' . $errMsg,
                $this->getLocation()
            );
        }

        if (count($this->formatters) == 0) {
          // turn legacy format attribute into formatter
          $fmt = new PhpCodeSnifferTask_FormatterElement();
          $fmt->setType($this->format);
          $fmt->setUseFile(false);
          $this->formatters[] = $fmt;
        }

        if (!isset($this->file))
        {
            $fileList = array();
            $project = $this->getProject();
            foreach ($this->filesets as $fs) {
                $ds = $fs->getDirectoryScanner($project);
                $files = $ds->getIncludedFiles();
                $dir = $fs->getDir($this->project)->getAbsolutePath();
                foreach ($files as $file) {
                    $fileList[] = $dir.DIRECTORY_SEPARATOR.$file;
                }
            }
        }

        $cwd = getcwd();
        
        // Save command line arguments because it confuses PHPCS (version 1.3.0)
        $oldArgs = $_SERVER['argv'];
        $_SERVER['argv'] = array();
        $_SERVER['argc'] = 0;
        
        include_once 'phing/tasks/ext/phpcs/PhpCodeSnifferTask_Wrapper.php';
        
        $codeSniffer = new PhpCodeSnifferTask_Wrapper($this->verbosity, $this->tabWidth, $this->encoding);
        $codeSniffer->setAllowedFileExtensions($this->allowedFileExtensions);
        if (is_array($this->ignorePatterns)) $codeSniffer->setIgnorePatterns($this->ignorePatterns);
        foreach ($this->configData as $configData) {
            $codeSniffer->setConfigData($configData->getName(), $configData->getValue(), true);
        }

        if ($this->file instanceof PhingFile) {
            $codeSniffer->process($this->file->getPath(), $this->standard, $this->sniffs, $this->noSubdirectories);

        } else {
            $codeSniffer->process($fileList, $this->standard, $this->sniffs, $this->noSubdirectories);
        }
        $report = $this->printErrorReport($codeSniffer);

        // generate the documentation
        if ($this->docGenerator !== '' && $this->docFile !== null) {
            ob_start();

            $codeSniffer->generateDocs($this->standard, $this->sniffs, $this->docGenerator);

            $output = ob_get_contents();
            ob_end_clean();

            // write to file
            $outputFile = $this->docFile->getPath();
            $check      = file_put_contents($outputFile, $output);

            if (is_bool($check) && !$check) {
                throw new BuildException('Error writing doc to ' . $outputFile);
            }
        } elseif ($this->docGenerator !== '' && $this->docFile === null) {
            $codeSniffer->generateDocs($this->standard, $this->sniffs, $this->docGenerator);
        }

        if ($this->haltonerror && $report['totals']['errors'] > 0)
        {
            throw new BuildException('phpcodesniffer detected ' . $report['totals']['errors']. ' error' . ($report['totals']['errors'] > 1 ? 's' : ''));
        }

        if ($this->haltonwarning && $report['totals']['warnings'] > 0)
        {
            throw new BuildException('phpcodesniffer detected ' . $report['totals']['warnings'] . ' warning' . ($report['totals']['warnings'] > 1 ? 's' : ''));
        }
        
        $_SERVER['argv'] = $oldArgs;
        $_SERVER['argc'] = count($oldArgs);
        chdir($cwd);
    }

    /**
     * Prints the error report.
     *
     * @param PHP_CodeSniffer $phpcs The PHP_CodeSniffer object containing
     *                               the errors.
     *
     * @return int The number of error and warning messages shown.
     */
    protected function printErrorReport($phpcs)
    {
        if ($this->showSniffs) {
            $sniffs = $phpcs->getSniffs();
            $sniffStr = '';
            foreach ($sniffs as $sniff) {
                if (is_string($sniff)) {
                    $sniffStr .= '- ' . $sniff . PHP_EOL;
                } else {
                    $sniffStr .= '- ' . get_class($sniff) . PHP_EOL;
                }
            }
            $this->log('The list of used sniffs (#' . count($sniffs) . '): ' . PHP_EOL . $sniffStr, Project::MSG_INFO);
        }

        $filesViolations = $phpcs->getFilesErrors();
        $reporting       = new PHP_CodeSniffer_Reporting();
        $report          = $reporting->prepare($filesViolations, $this->showWarnings);

        // process output
        foreach ($this->formatters as $fe) {
            switch ($fe->getType()) {
                case 'default':
                    // default format goes to logs, no buffering
                    $this->outputCustomFormat($report);
                    $fe->setUseFile(false);
                    break;

                default:
                    $reportFile = null;

                    if ($fe->getUseFile()) {
                        $reportFile = $fe->getOutfile();
                        ob_start();
                    }

                    // Determine number of parameters required to
                    // ensure backwards compatibility
                    $rm = new ReflectionMethod('PHP_CodeSniffer_Reporting', 'printReport');

                    if ($rm->getNumberOfParameters() == 5) {
                        $reporting->printReport(
                            $fe->getType(),
                            $filesViolations,
                            $this->showSources,
                            $reportFile,
                            $this->reportWidth
                        );
                    } else {
                        $reporting->printReport(
                            $fe->getType(),
                            $filesViolations,
                            $this->showWarnings,
                            $this->showSources,
                            $reportFile,
                            $this->reportWidth
                        );
                    }

                    // reporting class uses ob_end_flush(), but we don't want
                    // an output if we use a file
                    if ($fe->getUseFile()) {
                        ob_end_clean();
                    }
                    break;
            }
        }

        return $report;
    }

    /**
     * Outputs the results with a custom format
     *
     * @param array $report Packaged list of all errors in each file
     */
    protected function outputCustomFormat($report) {
        $files = $report['files'];
        foreach ($files as $file => $attributes) {
            $errors = $attributes['errors'];
            $warnings = $attributes['warnings'];
            $messages = $attributes['messages'];
            if ($errors > 0) {
                $this->log($file . ': ' . $errors . ' error' . ($errors > 1 ? 's' : '') . ' detected', Project::MSG_ERR);
                $this->outputCustomFormatMessages($messages, 'ERROR');
            } else {
                $this->log($file . ': No syntax errors detected', Project::MSG_VERBOSE);
            }
            if ($warnings > 0) {
                $this->log($file . ': ' . $warnings . ' warning' . ($warnings > 1 ? 's' : '') . ' detected', Project::MSG_WARN);
                $this->outputCustomFormatMessages($messages, 'WARNING');
            }
        }

        $totalErrors = $report['totals']['errors'];
        $totalWarnings = $report['totals']['warnings'];
        $this->log(count($files) . ' files were checked', Project::MSG_INFO);
        if ($totalErrors > 0) {
            $this->log($totalErrors . ' error' . ($totalErrors > 1 ? 's' : '') . ' detected', Project::MSG_ERR);
        } else {
            $this->log('No syntax errors detected', Project::MSG_INFO);
        }
        if ($totalWarnings > 0) {
            $this->log($totalWarnings . ' warning' . ($totalWarnings > 1 ? 's' : '') . ' detected', Project::MSG_INFO);
        }
    }

    /**
     * Outputs the messages of a specific type for one file
     * @param array $messages
     * @param string $type
     */
    protected function outputCustomFormatMessages($messages, $type) {
        foreach ($messages as $line => $messagesPerLine) {
            foreach ($messagesPerLine as $column => $messagesPerColumn) {
                foreach ($messagesPerColumn as $message) {
                    $msgType = $message['type'];
                    if ($type == $msgType) {
                        $logLevel = Project::MSG_INFO;
                        if ($msgType == 'ERROR') {
                            $logLevel = Project::MSG_ERR;
                        } else if ($msgType == 'WARNING') {
                            $logLevel = Project::MSG_WARN;
                        }
                        $text = $message['message'];
                        $string = $msgType . ' in line ' . $line . ' column ' . $column . ': ' . $text;
                        $this->log($string, $logLevel);
                    }
                }
            }
        }
    }

} //end phpCodeSnifferTask

/**
 * @package phing.tasks.ext
 */
class PhpCodeSnifferTask_FormatterElement extends DataType {

  /**
   * Type of output to generate
   * @var string
   */
  protected $type      = "";

  /**
   * Output to file?
   * @var bool
   */
  protected $useFile   = true;

  /**
   * Output file.
   * @var string
   */
  protected $outfile   = "";

  /**
   * Validate config.
   */
  public function parsingComplete () {
        if(empty($this->type)) {
            throw new BuildException("Format missing required 'type' attribute.");
    }
    if ($useFile && empty($this->outfile)) {
      throw new BuildException("Format requires 'outfile' attribute when 'useFile' is true.");
    }

  }

  public function setType ($type)  {
    $this->type = $type;
  }

  public function getType () {
    return $this->type;
  }

  public function setUseFile ($useFile) {
    $this->useFile = $useFile;
  }

  public function getUseFile () {
    return $this->useFile;
  }

  public function setOutfile ($outfile) {
    $this->outfile = $outfile;
  }

  public function getOutfile () {
    return $this->outfile;
  }

} //end FormatterElement
