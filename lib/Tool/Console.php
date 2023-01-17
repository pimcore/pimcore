<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tool;

use Pimcore\Config;
use Pimcore\Logger;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class Console
{
    protected static array $executableCache = [];

    /**
     * @return ($throwException is true ? string : string|false)
     *
     * @throws \Exception
     */
    public static function getExecutable(string $name, bool $throwException = false): string|false
    {
        if (isset(self::$executableCache[$name])) {
            if (!self::$executableCache[$name] && $throwException) {
                throw new \Exception("No '$name' executable found, please install the application or add it to the PATH (in system settings or to your PATH environment variable");
            }

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

        $paths = [];

        try {
            $systemConfig = Config::getSystemConfiguration('general');
            if (!empty($systemConfig['path_variable'])) {
                $paths = explode(PATH_SEPARATOR, $systemConfig['path_variable']);
            }
        } catch (\Exception $e) {
            Logger::warning((string) $e);
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
     * @throws \Exception
     */
    public static function getPhpCli(): string
    {
        try {
            return self::getExecutable('php', true);
        } catch (\Exception $e) {
            $phpFinder = new PhpExecutableFinder();
            $phpPath = $phpFinder->find(true);
            if (!$phpPath) {
                throw $e;
            }

            return $phpPath;
        }
    }

    public static function getTimeoutBinary(): string|false
    {
        return self::getExecutable('timeout');
    }

    /**
     * @param string[] $arguments
     *
     * @return string[]
     */
    protected static function buildPhpScriptCmd(string $script, array $arguments = []): array
    {
        $phpCli = self::getPhpCli();

        $cmd = [$phpCli, $script];

        if (Config::getEnvironment()) {
            array_push($cmd, '--env=' . Config::getEnvironment());
        }

        $cmd = array_merge($cmd, $arguments);

        return $cmd;
    }

    /**
     * @param string[] $arguments
     */
    public static function runPhpScript(string $script, array $arguments = [], string $outputFile = null, float $timeout = 60): string
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        self::addLowProcessPriority($cmd);
        $process = new Process($cmd);

        $process->setTimeout($timeout);

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
     * @param string[]|string $cmd
     *
     * @internal
     */
    public static function addLowProcessPriority(array|string &$cmd): void
    {
        $nice = (string) self::getExecutable('nice');
        if ($nice) {
            if (is_string($cmd)) {
                $cmd = $nice . ' -n 19 ' . $cmd;
            } elseif (is_array($cmd)) {
                array_unshift($cmd, $nice, '-n', '19');
            }
        }
    }
}
