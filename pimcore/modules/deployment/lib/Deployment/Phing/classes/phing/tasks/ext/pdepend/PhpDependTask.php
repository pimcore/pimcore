<?php
/**
 *  $Id: 572bbfe2e542b864211a85de9990f5cbfe31a4cd $
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
 * Runs the PHP_Depend software analyzer and metric tool.
 * Performs static code analysis on a given source base.
 *
 * @package phing.tasks.ext.pdepend
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: 572bbfe2e542b864211a85de9990f5cbfe31a4cd $
 * @since   2.4.1
 */
class PhpDependTask extends Task
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
     * List of allowed file extensions. Default file extensions are <b>php</b>
     * and <p>php5</b>.
     *
     * @var array<string>
     */
    protected $_allowedFileExtensions = array('php', 'php5');

    /**
     * List of exclude directories. Default exclude dirs are <b>.git</b>,
     * <b>.svn</b> and <b>CVS</b>.
     *
     * @var array<string>
     */
    protected $_excludeDirectories = array('.git', '.svn', 'CVS');

    /**
     * List of exclude packages
     *
     * @var array<string>
     */
    protected $_excludePackages = array();

    /**
     * Should the parse ignore doc comment annotations?
     *
     * @var boolean
     */
    protected $_withoutAnnotations = false;

    /**
     * Should PHP_Depend treat <b>+global</b> as a regular project package?
     *
     * @var boolean
     */
    protected $_supportBadDocumentation = false;

    /**
     * Flag for enable/disable debugging
     *
     * @var boolean
     */
    protected $_debug = false;

    /**
     * PHP_Depend configuration file
     *
     * @var PhingFile
     */
    protected $_configFile = null;

    /**
     * Logger elements
     *
     * @var array<PhpDependLoggerElement>
     */
    protected $_loggers = array();

    /**
     * Analyzer elements
     *
     * @var array<PhpDependAnalyzerElement>
     */
    protected $_analyzers = array();

    /**
     * Holds the PHP_Depend runner instance
     *
     * @var PHP_Depend_TextUI_Runner
     */
    protected $_runner = null;

    /**
     * Flag that determines whether to halt on error
     *
     * @var boolean
     */
    protected $_haltonerror = false;

    /**
     * Load the necessary environment for running PHP_Depend
     *
     * @return void
     * @throws BuildException
     */
    public function init()
    {
        /**
         * Determine PHP_Depend installation
         */
        @include_once 'PHP/Depend/TextUI/Runner.php';

        if (! class_exists('PHP_Depend_TextUI_Runner')) {
            throw new BuildException(
                'PhpDependTask depends on PHP_Depend being installed '
                . 'and on include_path',
                $this->getLocation()
            );
        }

        /**
         * Other dependencies that should only be loaded
         * when class is actually used
         */
        require_once 'phing/tasks/ext/pdepend/PhpDependLoggerElement.php';
        require_once 'phing/tasks/ext/pdepend/PhpDependAnalyzerElement.php';
        require_once 'PHP/Depend/Autoload.php';
    }

    /**
     * Set the input source file or directory
     *
     * @param PhingFile $file The input source file or directory
     *
     * @return void
     */
    public function setFile(PhingFile $file)
    {
        $this->_file = $file;
    }

    /**
     * Nested creator, adds a set of files (nested fileset attribute)
     *
     * @return FileSet The created fileset object
     */
    public function createFileSet()
    {
        $num = array_push($this->_filesets, new FileSet());
        return $this->_filesets[$num-1];
    }

    /**
     * Sets a list of filename extensions for valid php source code files
     *
     * @param string $fileExtensions List of valid file extensions
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
     * Sets a list of exclude directories
     *
     * @param string $excludeDirectories List of exclude directories
     *
     * @return void
     */
    public function setExcludeDirectories($excludeDirectories)
    {
        $this->_excludeDirectories = array();

        $token   = ' ,;';
        $pattern = strtok($excludeDirectories, $token);

        while ($pattern !== false) {
            $this->_excludeDirectories[] = $pattern;
            $pattern = strtok($token);
        }
    }

    /**
     * Sets a list of exclude packages
     *
     * @param string $excludePackages Exclude packages
     *
     * @return void
     */
    public function setExcludePackages($excludePackages)
    {
        $this->_excludePackages = array();

        $token   = ' ,;';
        $pattern = strtok($excludePackages, $token);

        while ($pattern !== false) {
            $this->_excludePackages[] = $pattern;
            $pattern = strtok($token);
        }
    }

    /**
     * Should the parser ignore doc comment annotations?
     *
     * @param boolean $withoutAnnotations
     *
     * @return void
     */
    public function setWithoutAnnotations($withoutAnnotations)
    {
        $this->_withoutAnnotations = StringHelper::booleanValue(
            $withoutAnnotations
        );
    }

    /**
     * Should PHP_Depend support projects with a bad documentation. If this
     * option is set to <b>true</b>, PHP_Depend will treat the default package
     * <b>+global</b> as a regular project package.
     *
     * @param boolean $supportBadDocumentation
     *
     * @return void
     */
    public function setSupportBadDocumentation($supportBadDocumentation)
    {
        $this->_supportBadDocumentation = StringHelper::booleanValue(
            $supportBadDocumentation
        );
    }

    /**
     * Set debugging On/Off
     *
     * @param boolean $debug
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->_debug = StringHelper::booleanValue($debug);
    }

    /**
     * Set halt on error
     *
     * @param boolean $haltonerror
     *
     * @return void
     */
    public function setHaltonerror($haltonerror)
    {
        $this->_haltonerror = StringHelper::booleanValue($haltonerror);
    }

    /**
     * Set the configuration file
     *
     * @param PhingFile $configFile The configuration file
     *
     * @return void
     */
    public function setConfigFile(PhingFile $configFile)
    {
        $this->_configFile = $configFile;
    }

    /**
     * Create object for nested logger element
     *
     * @return PhpDependLoggerElement
     */
    public function createLogger()
    {
        $num = array_push($this->_loggers, new PhpDependLoggerElement());
        return $this->_loggers[$num-1];
    }

    /**
     * Create object for nested analyzer element
     *
     * @return PhpDependAnalyzerElement
     */
    public function createAnalyzer()
    {
        $num = array_push($this->_analyzers, new PhpDependAnalyzerElement());
        return $this->_analyzers[$num-1];
    }

    /**
     * Executes PHP_Depend_TextUI_Runner against PhingFile or a FileSet
     *
     * @return void
     * @throws BuildException
     */
    public function main()
    {
        $autoload = new PHP_Depend_Autoload();
        $autoload->register();

        if (!isset($this->_file) and count($this->_filesets) == 0) {
            throw new BuildException(
                "Missing either a nested fileset or attribute 'file' set"
            );
        }

        if (count($this->_loggers) == 0) {
            throw new BuildException("Missing nested 'logger' element");
        }

        $this->validateLoggers();
        $this->validateAnalyzers();

        $filesToParse = array();

        if ($this->_file instanceof PhingFile) {
            $filesToParse[] = $this->_file->__toString();
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

        $this->_runner = new PHP_Depend_TextUI_Runner();
        $this->_runner->addProcessListener(new PHP_Depend_TextUI_ResultPrinter());

        $configurationFactory = new PHP_Depend_Util_Configuration_Factory();
        $configuration = $configurationFactory->createDefault();
        $this->_runner->setConfiguration($configuration);

        $this->_runner->setSourceArguments($filesToParse);

        foreach ($this->_loggers as $logger) {
            // Register logger
            $this->_runner->addLogger(
                $logger->getType(),
                $logger->getOutfile()->__toString()
            );
        }

        foreach ($this->_analyzers as $analyzer) {
            // Register additional analyzer
            $this->_runner->addOption(
                $analyzer->getType(),
                $analyzer->getValue()
            );
        }

        // Disable annotation parsing
        if ($this->_withoutAnnotations) {
            $this->_runner->setWithoutAnnotations();
        }

        // Enable bad documentation support
        if ($this->_supportBadDocumentation) {
            $this->_runner->setSupportBadDocumentation();
        }

        // Check for suffix
        if (count($this->_allowedFileExtensions) > 0) {
            $this->_runner->setFileExtensions($this->_allowedFileExtensions);
        }

        // Check for ignore directories
        if (count($this->_excludeDirectories) > 0) {
            $this->_runner->setExcludeDirectories($this->_excludeDirectories);
        }

        // Check for exclude packages
        if (count($this->_excludePackages) > 0) {
            $this->_runner->setExcludePackages($this->_excludePackages);
        }

        // Check for configuration option
        if ($this->_configFile instanceof PhingFile) {
            if (file_exists($this->_configFile->__toString()) === false) {
                throw new BuildException(
                    'The configuration file "'
                    . $this->_configFile->__toString() . '" doesn\'t exist.'
                );
            }

            // Load configuration file
            $config = new PHP_Depend_Util_Configuration(
                $this->_configFile->__toString(),
                null,
                true
            );

            // Store in config registry
            PHP_Depend_Util_ConfigurationInstance::set($config);
        }

        if ($this->_debug) {
            require_once 'PHP/Depend/Util/Log.php';
            // Enable debug logging
            PHP_Depend_Util_Log::setSeverity(PHP_Depend_Util_Log::DEBUG);
        }

        $this->_runner->run();

        if ($this->_runner->hasParseErrors() === true) {
            $this->log('Following errors occurred:');

            foreach ($this->_runner->getParseErrors() as $error) {
                $this->log($error);
            }

            if ($this->_haltonerror === true) {
                throw new BuildException('Errors occurred during parse process');
            }
        }
    }

    /**
     * Validates the available loggers
     *
     * @return void
     * @throws BuildException
     */
    protected function validateLoggers()
    {
        foreach ($this->_loggers as $logger) {
            if ($logger->getType() === '') {
                throw new BuildException(
                    "Logger missing required 'type' attribute"
                );
            }

            if ($logger->getOutfile() === null) {
                throw new BuildException(
                    "Logger requires 'outfile' attribute"
                );
            }
        }
    }

    /**
     * Validates the available analyzers
     *
     * @return void
     * @throws BuildException
     */
    protected function validateAnalyzers()
    {
        foreach ($this->_analyzers as $analyzer) {
            if ($analyzer->getType() === '') {
                throw new BuildException(
                    "Analyzer missing required 'type' attribute"
                );
            }

            if (count($analyzer->getValue()) === 0) {
                throw new BuildException(
                    "Analyzer missing required 'value' attribute"
                );
            }
        }
    }
}