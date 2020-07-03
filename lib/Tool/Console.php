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
use Pimcore\Logger;
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

                $checkCmd = 'which ' . escapeshellarg($executablePath);
                if (self::getSystemEnvironment() == 'windows') {
                    $checkCmd = 'where ' . escapeshellarg($path) . ':' . $name;
                }

                $fullQualifiedPath = shell_exec($checkCmd . ' ' . $executablePath);
                $fullQualifiedPath = trim(strtok($fullQualifiedPath, "\n")); // get the first line/result
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
     * @param string $arguments
     *
     * @return string
     */
    protected static function buildPhpScriptCmd($script, $arguments)
    {
        $phpCli = self::getPhpCli();

        $cmd = $phpCli . ' ' . $script;

        if (Config::getEnvironment()) {
            $cmd .= ' --env=' . Config::getEnvironment();
        }

        if (!empty($arguments)) {
            $cmd .= ' ' . $arguments;
        }

        return $cmd;
    }

    /**
     * @param string $script
     * @param string $arguments
     * @param string|null $outputFile
     * @param int|null $timeout
     *
     * @return string
     */
    public static function runPhpScript($script, $arguments = '', $outputFile = null, $timeout = null)
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        $return = self::exec($cmd, $outputFile, $timeout);

        return $return;
    }

    /**
     * @param string $script
     * @param string $arguments
     * @param string|null $outputFile
     *
     * @return int
     */
    public static function runPhpScriptInBackground($script, $arguments = '', $outputFile = null)
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        $return = self::execInBackground($cmd, $outputFile);

        return $return;
    }

    /**
     * @param string $cmd
     * @param string|null $outputFile
     * @param int|null $timeout
     *
     * @return string
     */
    public static function exec($cmd, $outputFile = null, $timeout = null)
    {
        if ($timeout && self::getTimeoutBinary()) {

            // check if --kill-after flag is supported in timeout
            if (self::$timeoutKillAfterSupport === null) {
                $out = self::exec(self::getTimeoutBinary() . ' --help');
                if (strpos($out, '--kill-after')) {
                    self::$timeoutKillAfterSupport = true;
                } else {
                    self::$timeoutKillAfterSupport = false;
                }
            }

            $killAfter = '';
            if (self::$timeoutKillAfterSupport) {
                $killAfter = ' -k 1m';
            }

            $cmd = self::getTimeoutBinary() . $killAfter . ' ' . $timeout . 's ' . $cmd;
        } elseif ($timeout) {
            Logger::warn('timeout binary not found, executing command without timeout');
        }

        if ($outputFile) {
            $cmd = $cmd . ' > '. $outputFile .' 2>&1';
        } else {
            // send stderr to /dev/null otherwise this goes to the apache error log and can fill it up pretty quickly
            if (self::getSystemEnvironment() != 'windows') {
                $cmd .= ' 2> /dev/null';
            }
        }

        Logger::debug('Executing command `' . $cmd . '` on the current shell');
        $return = shell_exec($cmd);

        return $return;
    }

    /**
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
            foreach (['PIMCORE_ENVIRONMENT', 'SYMFONY_ENV', 'APP_ENV'] as $envVarName) {
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
}
