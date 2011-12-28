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

    public static function execInBackground($cmd, $outputFile = null) {

        // windows systems
        if(stripos(php_uname("s"), "windows") !== false) {
            self::execInBackgroundWindows($cmd, $outputFile);
        } else {
            self::execInBackgroundUnix($cmd, $outputFile);
        }
    }

    protected static function execInBackgroundUnix ($cmd, $outputFile) {

        if(!$outputFile) {
            $outputFile = "/dev/null";
        }

        $commandWrapped = "/usr/bin/nohup " . $cmd . " > ". $outputFile ." 2>&1 & echo $!";
        Logger::debug("Executing command `" . $commandWrapped . "´ on the current shell");
        $pid = shell_exec($commandWrapped);

        Logger::debug("Process started with PID " . $pid);
    }

    protected static function execInBackgroundWindows($cmd, $outputFile) {

        if(!$outputFile) {
            $outputFile = "NUL";
        }

        $commandWrapped = "cmd /c " . $cmd . " > ". $outputFile . " 2>&1";
        Logger::debug("Executing command `" . $commandWrapped . "´ on the current shell");

        $WshShell = new COM("WScript.Shell");
        $WshShell->Run($commandWrapped, 0, false);
        Logger::debug("Process started");
    }
}
