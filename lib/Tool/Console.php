<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Config;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Console
{
    /**
     * @var string system environment
     */
    private static $systemEnvironment;

    /**
     * @var null|bool
     */
    protected static $timeoutKillAfterSupport = null;

    /**
     * @var array
     */
    protected static $executableCache = [];

    /**
     * @param string $name
     * @param bool $throwException
     *
     * @return bool|mixed|string
     *
     * @throws \Exception
     */
    public static function getExecutable($name, $throwException = false)
    {
        if (isset(self::$executableCache[$name])) {
            return self::$executableCache[$name];
        }

        // allow custom setup routines for certain programs
        $customSetupMethod = 'setup' . ucfirst($name);
        if (method_exists(__CLASS__, $customSetupMethod)) {
            self::$customSetupMethod();
        }

        // use DI to provide the ability to customize / overwrite paths
        if (\Pimcore::hasContainer() && \Pimcore::getContainer()->hasParameter('pimcore_executable_' . $name)) {
            $value = \Pimcore::getContainer()->getParameter('pimcore_executable_' . $name);
            if (!$value && $throwException) {
                throw new \Exception("'$name' executable was disabled manually in parameters.yml");
            }

            return $value;
        }

        $systemConfig = Config::getSystemConfiguration('general');

        $paths = [];
        if (!empty($systemConfig['path_variable'])) {
            $paths = explode(PATH_SEPARATOR, $systemConfig['path_variable']);
        }

        array_push($paths, '');

        // allow custom check routines for certain programs
        $customCheckMethod = 'check' . ucfirst($name);
        if (!method_exists(__CLASS__, $customCheckMethod)) {
            $customCheckMethod = null;
        }

        foreach ($paths as $path) {
            try {
                $path = rtrim($path, '/\\ ');
                if ($path) {
                    $executablePath = $path . DIRECTORY_SEPARATOR . $name;
                } else {
                    $executablePath = $name;
                }

                $executableFinder = new ExecutableFinder();
                $fullQualifiedPath = $executableFinder->find($executablePath);
                if ($fullQualifiedPath) {
                    if (!$customCheckMethod || self::$customCheckMethod($executablePath)) {
                        self::$executableCache[$name] = $fullQualifiedPath;

                        return $fullQualifiedPath;
                    }
                }
            } catch (\Exception $e) {
                // nothing to do ...
            }
        }

        self::$executableCache[$name] = false;

        if ($throwException) {
            throw new \Exception("No '$name' executable found, please install the application or add it to the PATH (in system settings or to your PATH environment variable");
        }

        return false;
    }

    protected static function setupComposer()
    {
        // composer needs either COMPOSER_HOME or HOME to be set
        // we also populate the $_ENV variable, it is used by symfony/process component
        if (!getenv('COMPOSER_HOME') && !getenv('HOME')) {
            $composerHome = PIMCORE_PRIVATE_VAR . '/composer';
            if (!is_dir($composerHome)) {
                mkdir($composerHome, 0777, true);
            }
            putenv('COMPOSER_HOME=' . $composerHome);
            $_ENV['COMPOSER_HOME'] = $composerHome;
        }

        putenv('COMPOSER_DISABLE_XDEBUG_WARN=true');
        $_ENV['COMPOSER_DISABLE_XDEBUG_WARN'] = 'true';
    }

    /**
     * @param string $executablePath
     *
     * @return bool
     */
    protected static function checkPngout($executablePath)
    {
        try {
            $process = new Process([$executablePath, '--help']);
            $process->run();
            if (strpos($process->getOutput() . $process->getErrorOutput(), 'bitdepth') !== false) {
                return true;
            }
        } catch (\Exception $e) {
            // noting to do
        }

        return false;
    }

    /**
     * @param string $executablePath
     *
     * @return bool
     */
    protected static function checkCjpeg($executablePath)
    {
        try {
            $process = new Process([$executablePath, '--help']);
            $process->run();
            if (strpos($process->getOutput() . $process->getErrorOutput(), '-optimize') !== false) {
                if (strpos($process->getOutput() . $process->getErrorOutput(), 'mozjpeg') !== false) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // noting to do
        }

        return false;
    }

    /**
     * @param string $process
     *
     * @return bool
     */
    protected static function checkComposite($process)
    {
        return self::checkConvert($process);
    }

    /**
     * @param string $executablePath
     *
     * @return bool
     */
    protected static function checkConvert($executablePath)
    {
        try {
            $process = new Process([$executablePath, '--help']);
            $process->run();
            if (strpos($process->getOutput() . $process->getErrorOutput(), 'imagemagick.org') !== false) {
                return true;
            }
        } catch (\Exception $e) {
            // noting to do
        }

        return false;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getPhpCli()
    {
        return self::getExecutable('php', true);
    }

    /**
     * @return bool|string
     */
    public static function getTimeoutBinary()
    {
        return self::getExecutable('timeout');
    }

    /**
     * @param string $script
     * @param string|array $arguments
     *
     * @return array
     */
    protected static function buildPhpScriptCmd($script, $arguments)
    {
        $phpCli = self::getPhpCli();

        $cmd = [$phpCli, $script];

        if (Config::getEnvironment()) {
            array_push($cmd, '--env=' . Config::getEnvironment());
        }

        if (!empty($arguments)) {
            if (is_string($arguments)) {
                @trigger_error(sprintf('Passing string arguments to %s is deprecated since v6.9 and will throw exception in Pimcore 10. Pass array arguments instead.', __METHOD__), E_USER_DEPRECATED);
                $arguments = explode(' ', $arguments);
            }
            $cmd = array_merge($cmd, $arguments);
        }

        return $cmd;
    }

    /**
     * @param string $script
     * @param string|array $arguments
     * @param string|null $outputFile
     * @param int|null $timeout
     * @param bool $background
     *
     * @return string|int
     */
    public static function runPhpScript($script, $arguments = '', $outputFile = null, $timeout = null, $background = false)
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        self::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        if ($timeout) {
            $process->setTimeout($timeout);
        }
        $process->start();

        if (!empty($outputFile)) {
            $logHandle = fopen($outputFile, 'a');
            $exitCode = $process->wait(function ($type, $buffer) use ($logHandle) {
                fwrite($logHandle, $buffer);
            });
            fclose($logHandle);
        } else {
            $exitCode = $process->wait();
        }

        return $background ? $exitCode : $process->getOutput();
    }

    /**
     * Returns a hash with all options passed to a cli script
     *
     * @param bool $onlyFullNotationArgs
     *
     * @return array
     */
    public static function getOptions($onlyFullNotationArgs = false)
    {
        global $argv;
        $options = [];
        $tmpOptions = $argv;
        array_shift($tmpOptions);

        foreach ($tmpOptions as $optionString) {
            if ($onlyFullNotationArgs && substr($optionString, 0, 2) != '--') {
                continue;
            }
            $exploded = explode('=', $optionString, 2);
            $options[str_replace('-', '', $exploded[0])] = $exploded[1];
        }

        return $options;
    }

    /**
     * @param array $options
     * @param string $concatenator
     * @param string $arrayConcatenator
     *
     * @return string
     */
    public static function getOptionString($options, $concatenator = '=', $arrayConcatenator = ',')
    {
        $string = '';

        foreach ($options as $key => $value) {
            $string .= '--' . $key;
            if ($value) {
                if (is_array($value)) {
                    $value = implode($arrayConcatenator, $value);
                }
                $string .= $concatenator . "'" . $value . "'";
            }
            $string .= ' ';
        }

        return $string;
    }

    /**
     * @throws \Exception
     */
    public static function checkCliExecution()
    {
        if (php_sapi_name() != 'cli') {
            throw new \Exception('Script execution is restricted to CLI');
        }
    }

    /**
     * @internal
     *
     * @param array|string $cmd
     *
     * @return array|string
     */
    public static function addLowProcessPriority($cmd)
    {
        $nice = (string) self::getExecutable('nice');
        if ($nice) {
            if (is_string($cmd)) {
                $cmd = $nice . ' -n 19 ' . $cmd;
            } elseif (is_array($cmd)) {
                array_unshift($cmd, $nice, '-n 19');
            }
        }

        return $cmd;
    }
}
