<?php
/*
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
 * ApiGen task (http://apigen.org).
 *
 * @package   phing.tasks.ext.apigen
 * @author    Martin Srank <martin@smasty.net>
 * @author    Jaroslav Hanslík <kukulich@kukulich.cz>
 * @since     2.4.10
 */
class ApiGenTask extends Task
{
    /**
     * Default ApiGen executable name.
     *
     * @var string
     */
    private $executable = 'apigen';

    /**
     * Default options for ApiGen.
     *
     * @var array
     */
    private $options = array(
        'progressbar' => false,
        'colors' => false,
        'update-check' => false
    );

    /**
     * Sets the ApiGen executable name.
     *
     * @param string $executable
     */
    public function setExecutable($executable)
    {
        $this->executable = (string) $executable;
    }

    /**
     * Sets the config file name.
     *
     * @param string $config
     */
    public function setConfig($config)
    {
        $this->options['config'] = (string) $config;
    }

    /**
     * Sets source files or directories.
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->options['source'] = explode(',', $source);
    }

    /**
     * Sets the destination directory.
     *
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->options['destination'] = (string) $destination;
    }

    /**
     * Sets list of allowed file extensions.
     *
     * @param string $extensions
     */
    public function setExtensions($extensions)
    {
        $this->options['extensions'] = explode(',', $extensions);
    }

    /**
     * Sets masks (case sensitive) to exclude files or directories from processing.
     *
     * @param string $exclude
     */
    public function setExclude($exclude)
    {
        $this->options['exclude'] = explode(',', $exclude);
    }

    /**
     * Sets masks to exclude elements from documentation generating.
     *
     * @param string $skipDocPath
     */
    public function setSkipDocPath($skipDocPath)
    {
        $this->options['skip-doc-path'] = explode(',', $skipDocPath);
    }

    /**
     * Sets a name prefix to exclude elements from documentation generating.
     *
     * @param string $skipDocPrefix
     */
    public function setSkipDocPrefix($skipDocPrefix)
    {
        $this->options['skip-doc-prefix'] = explode(',', $skipDocPrefix);
    }

    /**
     * Sets the character set of source files.
     *
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->options['charset'] = explode(',', $charset);
    }

    /**
     * Sets the main project name prefix.
     *
     * @param string $main
     */
    public function setMain($main)
    {
        $this->options['main'] = (string) $main;
    }

    /**
     * Sets the title of generated documentation.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->options['title'] = (string) $title;
    }

    /**
     * Sets the documentation base URL.
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->options['base-url'] = (string) $baseUrl;
    }

    /**
     * Sets the Google Custom Search ID.
     *
     * @param string $googleCseId
     */
    public function setGoogleCseId($googleCseId)
    {
        $this->options['google-cse-id'] = (string) $googleCseId;
    }

    /**
     * Sets the Google Custom Search label.
     *
     * @param string $googleCseLabel
     */
    public function setGoogleCseLabel($googleCseLabel)
    {
        $this->options['google-cse-label'] = (string) $googleCseLabel;
    }

    /**
     * Sets the Google Analytics tracking code.
     *
     * @param string $googleAnalytics
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->options['google-analytics'] = (string) $googleAnalytics;
    }

    /**
     * Sets the template config file name.
     *
     * @param string $templateConfig
     */
    public function setTemplateConfig($templateConfig)
    {
        $this->options['template-config'] = (string) $templateConfig;
    }

    /**
     * Sets a list of HTML tags allowed in the documentation.
     *
     * @param string $allowedHtml
     */
    public function setAllowedHtml($allowedHtml)
    {
        $this->options['allowed-html'] = (string) $allowedHtml;
    }

    /**
     * Sets how elements should be grouped in the menu.
     *
     * @param string $groups
     */
    public function setGroups($groups)
    {
        $this->options['groups'] = (string) $groups;
    }

    /**
     * Sets element types for search input autocomplete.
     *
     * @param string $autocomplete
     */
    public function setAutocomplete($autocomplete)
    {
        $this->options['autocomplete'] = (string) $autocomplete;
    }

    /**
     * Sets the element access levels.
     *
     * Documentation only for methods and properties with the given access level will be generated.
     *
     * @param string $accessLevels
     */
    public function setAccessLevels($accessLevels)
    {
        $this->options['access-levels'] = (string) $accessLevels;
    }

    /**
     * Sets if documentation for elements marked as internal and internal documentation parts should be generated.
     *
     * @param boolean $internal
     */
    public function setInternal($internal)
    {
        $this->options['internal'] = (bool) $internal;
    }

    /**
     * Sets if documentation for PHP internal classes should be generated.
     *
     * @param boolean $php
     */
    public function setPhp($php)
    {
        $this->options['php'] = (bool) $php;
    }

    /**
     * Sets if tree view of classes, interfaces, traits and exceptions should be generated.
     *
     * @param boolean $tree
     */
    public function setTree($tree)
    {
        $this->options['tree'] = (bool) $tree;
    }

    /**
     * Sets if documentation for deprecated elements should be generated.
     *
     * @param boolean $deprecated
     */
    public function setDeprecated($deprecated)
    {
        $this->options['deprecated'] = (bool) $deprecated;
    }

    /**
     * Sets if documentation of tasks should be generated.
     *
     * @param boolean $todo
     */
    public function setTodo($todo)
    {
        $this->options['todo'] = (bool) $todo;
    }

    /**
     * Sets if highlighted source code files should be generated.
     *
     * @param boolean $sourceCode
     */
    public function setSourceCode($sourceCode)
    {
        $this->options['source-code'] = (bool) $sourceCode;
    }

    /**
     * Sets if a link to download documentation as a ZIP archive should be generated.
     *
     * @param boolean $download
     */
    public function setDownload($download)
    {
        $this->options['download'] = (bool) $download;
    }

    /**
     * Sets a file name for checkstyle report of poorly documented elements.
     *
     * @param string $report
     */
    public function setReport($report)
    {
        $this->options['report'] = (string) $report;
    }

    /**
     * Sets if the destination directory should be wiped out first.
     *
     * @param boolean $wipeout
     */
    public function setWipeout($wipeout)
    {
        $this->options['wipeout'] = (bool) $wipeout;
    }

    /**
     * Enables/disables scaning and generating messages.
     *
     * @param boolean $quiet
     */
    public function setQuiet($quiet)
    {
        $this->options['quiet'] = (bool) $quiet;
    }

    /**
     * Enables/disables the check for ApiGen updates.
     *
     * @param boolean $updateCheck
     */
    public function setUpdateCheck($updateCheck)
    {
        $this->options['update-check'] = (bool) $updateCheck;
    }

    /**
     * Enables/disables the debug mode.
     *
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->options['debug'] = (bool) $debug;
    }

    /**
     * Runs ApiGen.
     *
     * @throws BuildException If something is wrong.
     * @see Task::main()
     */
    public function main()
    {
        if ('apigen' !== $this->executable && !is_file($this->executable)) {
            throw new BuildException(sprintf('Executable %s not found', $this->executable), $this->getLocation());
        }

        if (!empty($this->options['config'])) {
            // Config check
            if (!is_file($this->options['config'])) {
                throw new BuildException(sprintf('Config file %s doesn\'t exist', $this->options['config']), $this->getLocation());
            }
        } else {
            // Source check
            if (empty($this->options['source'])) {
                throw new BuildException('Source is not set', $this->getLocation());
            }
            // Destination check
            if (empty($this->options['destination'])) {
                throw new BuildException('Destination is not set', $this->getLocation());
            }
        }

        // Source check
        if (!empty($this->options['source'])) {
            foreach ($this->options['source'] as $source) {
                if (!file_exists($source)) {
                    throw new BuildException(sprintf('Source %s doesn\'t exist', $source), $this->getLocation());
                }
            }
        }

        // Execute ApiGen
        exec(escapeshellcmd($this->executable) . ' ' . $this->constructArguments(), $output, $return);

        $logType = 0 === $return ? Project::MSG_INFO : Project::MSG_ERR;
        foreach ($output as $line) {
            $this->log($line, $logType);
        }
    }

    /**
     * Generates command line arguments for the ApiGen executable.
     *
     * @return string
     */
    protected function constructArguments()
    {
        $args = array();
        foreach ($this->options as $option => $value) {
            if (is_bool($value)) {
                $args[] = '--' . $option . '=' . ($value ? 'yes' : 'no');
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    $args[] = '--' . $option . '=' . escapeshellarg($v);
                }
            } else {
                $args[] = '--' . $option . '=' . escapeshellarg($value);
            }
        }
        return implode(' ', $args);
    }
}
