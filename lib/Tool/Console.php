<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Config;
use Pimcore\Logger;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Console
{
    /**
     * @var string system environment
     */
    private static $systemEnvironment;

    /**
     * @var array
     */
    protected static $executableCache = [];

    /**
     * @deprecated since v.6.9.
     * @static
     *
     * @return string "windows" or "unix"
     */
    public static function getSystemEnvironment()
    {
        if (self::$systemEnvironment == null) {
            if (stripos(php_uname('s'), 'windows') !== false) {
                self::$systemEnvironment = 'windows';
            } elseif (stripos(php_uname('s'), 'darwin') !== false) {
                self::$systemEnvironment = 'darwin';
            } else {
                self::$systemEnvironment = 'unix';
            }
        }

        return self::$systemEnvironment;
    }

    /**
     * @param string $name
     * @param bool $throwException
     *
     * @return bool|string
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

            if ($value === false) {
                if ($throwException) {
                    throw new \Exception("'$name' executable was disabled manually in parameters.yml");
                }

                return false;
            }

            if ($value) {
                return $value;
            }
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
     * @param array $arguments
     *
     * @return array
     */
    protected static function buildPhpScriptCmd(string $script, array $arguments = [])
    {
        $phpCli = self::getPhpCli();

        $cmd = [$phpCli, $script];

        if (Config::getEnvironment()) {
            array_push($cmd, '--env=' . Config::getEnvironment());
        }

        if (!empty($arguments)) {
            $cmd = array_merge($cmd, $arguments);
        }

        return $cmd;
    }

    /**
     * @param string $script
     * @param array $arguments
     * @param string|null $outputFile
     * @param int|null $timeout
     *
     * @return string
     */
    public static function runPhpScript($script, $arguments = [], $outputFile = null, $timeout = null)
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
            $process->wait(function ($type, $buffer) use ($logHandle) {
                fwrite($logHandle, $buffer);
            });
            fclose($logHandle);
        } else {
            $process->wait();
        }

        return $process->getOutput();
    }

    /**
     * @deprecated since v6.9. For long running background tasks switch to a queue implementation.
     *
     * @param string $script
     * @param array $arguments
     * @param string|null $outputFile
     *
     * @return int
     */
    public static function runPhpScriptInBackground($script, $arguments = [], $outputFile = null)
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        $process = new Process($cmd);
        $commandLine = $process->getCommandLine();

        return self::execInBackground($commandLine, $outputFile);
    }

    /**
     * @deprecated since v.6.9. Use Symfony\Component\Process\Process instead. For long running background tasks use queues.
     * @static
     *
     * @param string $cmd
     * @param null|string $outputFile
     *
     * @return int
     */
    public static function execInBackground($cmd, $outputFile = null)
    {
        // windows systems
        if (self::getSystemEnvironment() == 'windows') {
            return self::execInBackgroundWindows($cmd, $outputFile);
        } elseif (self::getSystemEnvironment() == 'darwin') {
            return self::execInBackgroundUnix($cmd, $outputFile, false);
        } else {
            return self::execInBackgroundUnix($cmd, $outputFile);
        }
    }

    /**
     * @deprecated since v.6.9. For long running background tasks use queues.
     * @static
     *
     * @param string $cmd
     * @param string $outputFile
     * @param bool $useNohup
     *
     * @return int
     */
    protected static function execInBackgroundUnix($cmd, $outputFile, $useNohup = true)
    {
        if (!$outputFile) {
            $outputFile = '/dev/null';
        }

        $nice = (string) self::getExecutable('nice');
        if ($nice) {
            $nice .= ' -n 19 ';
        }

        if ($useNohup) {
            $nohup = (string) self::getExecutable('nohup');
            if ($nohup) {
                $nohup .= ' ';
            }
        } else {
            $nohup = '';
        }

        /**
         * mod_php seems to lose the environment variables if we do not set them manually before the child process is started
         */
        if (strpos(php_sapi_name(), 'apache') !== false) {
            foreach (['APP_ENV'] as $envVarName) {
                if ($envValue = $_SERVER[$envVarName] ?? $_SERVER['REDIRECT_' . $envVarName] ?? null) {
                    putenv($envVarName . '='.$envValue);
                }
            }
        }

        $commandWrapped = $nohup . $nice . $cmd . ' > '. $outputFile .' 2>&1 & echo $!';
        Logger::debug('Executing command `' . $commandWrapped . '´ on the current shell in background');
        $pid = shell_exec($commandWrapped);

        Logger::debug('Process started with PID ' . $pid);

        return (int)$pid;
    }

    /**
     * @deprecated since v.6.9. For long running background tasks use queues.
     * @static
     *
     * @param string $cmd
     * @param string $outputFile
     *
     * @return int
     */
    protected static function execInBackgroundWindows($cmd, $outputFile)
    {
        if (!$outputFile) {
            $outputFile = 'NUL';
        }

        $commandWrapped = 'cmd /c ' . $cmd . ' > '. $outputFile . ' 2>&1';
        Logger::debug('Executing command `' . $commandWrapped . '´ on the current shell in background');

        $WshShell = new \COM('WScript.Shell');
        $WshShell->Run($commandWrapped, 0, false);
        Logger::debug('Process started - returning the PID is not supported on Windows Systems');

        return 0;
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
