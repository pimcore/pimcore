<?php
/**
 * $Id: 4554fdd642b6ef7774cbb89c537ccba90f3ca972 $
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
require_once 'phing/system/io/PhingFile.php';
require_once 'phing/system/io/Writer.php';
require_once 'phing/util/LogWriter.php';

/**
 * Runs PHPUnit tests.
 *
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: 4554fdd642b6ef7774cbb89c537ccba90f3ca972 $
 * @package phing.tasks.ext.phpunit
 * @see BatchTest
 * @since 2.1.0
 */
class PHPUnitTask extends Task
{
    private $batchtests = array();
    private $formatters = array();
    private $bootstrap = "";
    private $haltonerror = false;
    private $haltonfailure = false;
    private $haltonincomplete = false;
    private $haltonskipped = false;
    private $errorproperty;
    private $failureproperty;
    private $incompleteproperty;
    private $skippedproperty;
    private $printsummary = false;
    private $testfailed = false;
    private $testfailuremessage = "";
    private $codecoverage = null;
    private $groups = array();
    private $excludeGroups = array();
    private $processIsolation = false;
    private $usecustomerrorhandler = true;
    
    /**
     * @var PhingFile
     */
    private $configuration = null;

    /**
     * Initialize Task.
     * This method includes any necessary PHPUnit libraries and triggers
     * appropriate error if they cannot be found.  This is not done in header
     * because we may want this class to be loaded w/o triggering an error.
     */
    public function init() {
        /**
         * Determine PHPUnit version number
         */
        @include_once 'PHPUnit/Runner/Version.php';
        
        if (!class_exists('PHPUnit_Runner_Version')) {
            throw new BuildException("PHPUnitTask requires PHPUnit to be installed", $this->getLocation());
        }

        $version = PHPUnit_Runner_Version::id();

        if (version_compare($version, '3.6.0') < 0)
        {
            throw new BuildException("PHPUnitTask requires PHPUnit version >= 3.6.0", $this->getLocation());
        }
            
        /**
         * Other dependencies that should only be loaded when class is actually used.
         */
        require_once 'phing/tasks/ext/phpunit/PHPUnitTestRunner.php';
        require_once 'phing/tasks/ext/phpunit/BatchTest.php';
        require_once 'phing/tasks/ext/phpunit/FormatterElement.php';

        /**
         * point PHPUnit_MAIN_METHOD define to non-existing method
         */
        if (!defined('PHPUnit_MAIN_METHOD'))
        {
            define('PHPUnit_MAIN_METHOD', 'PHPUnitTask::undefined');
        }
    }
    
    /**
     * Sets the name of a bootstrap file that is run before
     * executing the tests
     *
     * @param string $bootstrap the name of the bootstrap file
     */
    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }
    
    public function setErrorproperty($value)
    {
        $this->errorproperty = $value;
    }
    
    public function setFailureproperty($value)
    {
        $this->failureproperty = $value;
    }
    
    public function setIncompleteproperty($value)
    {
        $this->incompleteproperty = $value;
    }
    
    public function setSkippedproperty($value)
    {
        $this->skippedproperty = $value;
    }
    
    public function setHaltonerror($value)
    {
        $this->haltonerror = $value;
    }

    public function setHaltonfailure($value)
    {
        $this->haltonfailure = $value;
    }
    
    public function getHaltonfailure()
    {
        return $this->haltonfailure;
    }

    public function setHaltonincomplete($value)
    {
        $this->haltonincomplete = $value;
    }
    
    public function getHaltonincomplete()
    {
        return $this->haltonincomplete;
    }

    public function setHaltonskipped($value)
    {
        $this->haltonskipped = $value;
    }
    
    public function getHaltonskipped()
    {
        return $this->haltonskipped;
    }

    public function setPrintsummary($printsummary)
    {
        $this->printsummary = $printsummary;
    }
    
    public function setCodecoverage($codecoverage)
    {
        $this->codecoverage = $codecoverage;
    }

    public function setProcessIsolation($processIsolation)
    {
        $this->processIsolation = $processIsolation;
    }

    public function setUseCustomErrorHandler($usecustomerrorhandler)
    {
        $this->usecustomerrorhandler = $usecustomerrorhandler;
    }

    public function setGroups($groups)
    {
        $token = ' ,;';
        $this->groups = array();
        $tok = strtok($groups, $token);
        while ($tok !== false) {
            $this->groups[] = $tok;
            $tok = strtok($token);
        }
    }

    public function setExcludeGroups($excludeGroups)
    {
        $token = ' ,;';
        $this->excludeGroups = array();
        $tok = strtok($excludeGroups, $token);
        while ($tok !== false) {
            $this->excludeGroups[] = $tok;
            $tok = strtok($token);
        }
    }

    /**
     * Add a new formatter to all tests of this task.
     *
     * @param FormatterElement formatter element
     */
    public function addFormatter(FormatterElement $fe)
    {
        $fe->setParent($this);
        $this->formatters[] = $fe;
    }
    
    /**
     * @param PhingFile $configuration
     */
    public function setConfiguration(PhingFile $configuration)
    {
        $this->configuration = $configuration;
    }
    
    /**
     * Load and processes the PHPUnit configuration
     */
    protected function handlePHPUnitConfiguration($configuration)
    {
        if (!$configuration->exists()) {
            throw new BuildException("Unable to find PHPUnit configuration file '" . (string) $configuration . "'");
        }
        
        $config = PHPUnit_Util_Configuration::getInstance($configuration->getAbsolutePath());
        
        if (empty($configuration)) {
            return;
        }
        
        $phpunit = $config->getPHPUnitConfiguration();
        
        if (empty($phpunit)) {
            return;
        }
        
        $config->handlePHPConfiguration();
        
        if (isset($phpunit['bootstrap'])) {
            $this->setBootstrap($phpunit['bootstrap']);
        }
        
        if (isset($phpunit['stopOnFailure'])) {
            $this->setHaltonfailure($phpunit['stopOnFailure']);
        }

        if (isset($phpunit['stopOnError'])) {
            $this->setHaltonerror($phpunit['stopOnError']);
        }

        if (isset($phpunit['stopOnFailure'])) {
            $this->setHaltonskipped($phpunit['stopOnSkipped']);
        }

        if (isset($phpunit['stopOnIncomplete'])) {
            $this->setHaltonincomplete($phpunit['stopOnIncomplete']);
        }

        if (isset($phpunit['processIsolation'])) {
            $this->setProcessIsolation($phpunit['processIsolation']);
        }
    }

    /**
     * The main entry point
     *
     * @throws BuildException
     */
    public function main()
    {
        if ($this->codecoverage && !extension_loaded('xdebug'))
        {
            throw new Exception("PHPUnitTask depends on Xdebug being installed to gather code coverage information.");
        }
        
        if ($this->configuration) {
            $this->handlePHPUnitConfiguration($this->configuration);
        }
        
        if ($this->printsummary)
        {
            $fe = new FormatterElement();
            $fe->setParent($this);
            $fe->setType("summary");
            $fe->setUseFile(false);
            $this->formatters[] = $fe;
        }
        
        $autoloadSave = spl_autoload_functions();
        
        if ($this->bootstrap) {
            require $this->bootstrap;
        }
        
        $suite = new PHPUnit_Framework_TestSuite('AllTests');
        
        foreach ($this->batchtests as $batchtest)
        {
            $batchtest->addToTestSuite($suite);
        }
        
        $this->execute($suite);
        
        if ($this->testfailed)
        {
            throw new BuildException($this->testfailuremessage);
        }
        
        $autoloadNew = spl_autoload_functions();
        foreach ($autoloadNew as $autoload) {
            spl_autoload_unregister($autoload);
        }
        
        foreach ($autoloadSave as $autoload) {
            spl_autoload_register($autoload);
        }
    }

    /**
     * @throws BuildException
     */
    protected function execute($suite)
    {
        $runner = new PHPUnitTestRunner($this->project, $this->groups, $this->excludeGroups, $this->processIsolation);
        
        if ($this->codecoverage) {
            /**
             * Add some defaults to the PHPUnit filter
             */
            $pwd = dirname(__FILE__);
            $path = realpath($pwd . '/../../../');
            
            $filter = new PHP_CodeCoverage_Filter();
            $filter->addDirectoryToBlacklist($path);
            $runner->setCodecoverage(new PHP_CodeCoverage(null, $filter));
        }
        
        $runner->setUseCustomErrorHandler($this->usecustomerrorhandler);

        foreach ($this->formatters as $fe)
        {
            $formatter = $fe->getFormatter();

            if ($fe->getUseFile())
            {
                $destFile = new PhingFile($fe->getToDir(), $fe->getOutfile());
                
                $writer = new FileWriter($destFile->getAbsolutePath());

                $formatter->setOutput($writer);
            }
            else
            {
                $formatter->setOutput($this->getDefaultOutput());
            }

            $runner->addFormatter($formatter);

            $formatter->startTestRun();
        }
        
        $runner->run($suite);

        foreach ($this->formatters as $fe)
        {
            $formatter = $fe->getFormatter();
            $formatter->endTestRun();
        }
        
        $retcode = $runner->getRetCode();
        
        if ($retcode == PHPUnitTestRunner::ERRORS) {
            if ($this->errorproperty) {
                $this->project->setNewProperty($this->errorproperty, true);
            }
            if ($this->haltonerror) {
                $this->testfailed = true;
                $this->testfailuremessage = $runner->getLastErrorMessage();
            }
        } elseif ($retcode == PHPUnitTestRunner::FAILURES) {
            if ($this->failureproperty) {
                $this->project->setNewProperty($this->failureproperty, true);
            }
            
            if ($this->haltonfailure) {
                $this->testfailed = true;
                $this->testfailuremessage = $runner->getLastFailureMessage();
            }
        } elseif ($retcode == PHPUnitTestRunner::INCOMPLETES) {
            if ($this->incompleteproperty) {
                $this->project->setNewProperty($this->incompleteproperty, true);
            }
            
            if ($this->haltonincomplete) {
                $this->testfailed = true;
                $this->testfailuremessage = $runner->getLastIncompleteMessage();
            }
        } elseif ($retcode == PHPUnitTestRunner::SKIPPED) {
            if ($this->skippedproperty) {
                $this->project->setNewProperty($this->skippedproperty, true);
            }
            
            if ($this->haltonskipped) {
                $this->testfailed = true;
                $this->testfailuremessage = $runner->getLastSkippedMessage();
            }
        }
    }

    protected function getDefaultOutput()
    {
        return new LogWriter($this);
    }

    /**
     * Adds a set of tests based on pattern matching.
     *
     * @return BatchTest a new instance of a batch test.
     */
    public function createBatchTest()
    {
        $batchtest = new BatchTest($this->getProject());

        $this->batchtests[] = $batchtest;

        return $batchtest;
    }
}

