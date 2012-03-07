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

class Pimcore_Log_Simple {

    public static function log($name, $message) {

        $log = PIMCORE_LOG_DIRECTORY . "/$name.log";
        if(!is_file($log)) {
            if(is_writable(dirname($log))) {
                file_put_contents($log, "AUTOCREATE\n");
            }
        }

        if (is_writable($log)) {
            // check for big logfile, empty it if it's bigger than about 200M
            if (filesize($log) > 200000000) {
                file_put_contents($log, "");
            }

            $f = fopen($log, "a+");
            fwrite($f, Zend_Date::now()->getIso() . " : " . $message . "\n");
            fclose($f);
        }
    }
}