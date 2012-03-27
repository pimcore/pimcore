<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Console {

    /**
     * @static
     * @return string
     */
    public static function getPhpCli () {

        if(Pimcore_Config::getSystemConfig()->general->php_cli) {
            if(is_executable(Pimcore_Config::getSystemConfig()->general->php_cli)) {
                return (string) Pimcore_Config::getSystemConfig()->general->php_cli;
            } else {
                Logger::critical("PHP-CLI binary: " . Pimcore_Config::getSystemConfig()->general->php_cli . " is not executable");
            }
        }

        $paths = array("/usr/bin/php","/usr/local/bin/php","/usr/local/zend/bin/php", "/bin/php");

        foreach ($paths as $path) {
            if(is_executable($path)) {
                return $path;
            }
        }

        throw new Exception("No php executable found, please configure the correct path in the system settings");
    }

    /**
     * @static
     * @param $cmd
     * @param null $outputFile
     */
    public static function exec ($cmd, $outputFile = null) {

        if(!$outputFile) {
            if(stripos(php_uname("s"), "windows") !== false) {
                $outputFile = "NUL";
            } else {
                $outputFile = "/dev/null";
            }
        }

        $commandWrapped = $cmd . " > ". $outputFile ." 2>&1";
        Logger::debug("Executing command `" . $commandWrapped . "´ on the current shell");
        $pid = shell_exec($commandWrapped);

        Logger::debug("Process started with PID " . $pid);
    }

    /**
     * @static
     * @param string $cmd
     * @param null|string $outputFile
     * @return int
     */
    public static function execInBackground($cmd, $outputFile = null) {

        // windows systems
        if(stripos(php_uname("s"), "windows") !== false) {
            return self::execInBackgroundWindows($cmd, $outputFile);
        } else {
            return self::execInBackgroundUnix($cmd, $outputFile);
        }
    }

    /**
     * @static
     * @param string $cmd
     * @param string $outputFile
     * @return int
     */
    protected static function execInBackgroundUnix ($cmd, $outputFile) {

        if(!$outputFile) {
            $outputFile = "/dev/null";
        }

        $nice = "";
        if(is_executable("/usr/bin/nice")) {
            $nice = "/usr/bin/nice -n 19 ";
        }

        $commandWrapped = "/usr/bin/nohup " . $nice . $cmd . " > ". $outputFile ." 2>&1 & echo $!";
        Logger::debug("Executing command `" . $commandWrapped . "´ on the current shell in background");
        $pid = shell_exec($commandWrapped);

        Logger::debug("Process started with PID " . $pid);

        return $pid;
    }

    /**
     * @static
     * @param string $cmd
     * @param string $outputFile
     * @return int
     */
    protected static function execInBackgroundWindows($cmd, $outputFile) {

        if(!$outputFile) {
            $outputFile = "NUL";
        }

        $commandWrapped = "cmd /c " . $cmd . " > ". $outputFile . " 2>&1";
        Logger::debug("Executing command `" . $commandWrapped . "´ on the current shell in background");

        $WshShell = new COM("WScript.Shell");
        $WshShell->Run($commandWrapped, 0, false);
        Logger::debug("Process started - returning the PID is not supported on Windows Systems");

        return 0;
    }
}
