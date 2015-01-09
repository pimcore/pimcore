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
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\Model\Asset;

class Service {

    /**
     * @return string
     */
    public static function getDeleteLogFile () {
        return PIMCORE_WEBDAV_TEMP . "/delete.dat";
    }

    /**
     * @return array|mixed
     */
    public static function getDeleteLog () {
        $log = array();
        if(file_exists(self::getDeleteLogFile())) {
            $log = unserialize(file_get_contents(self::getDeleteLogFile()));
            if(!is_array($log)) {
                $log = array();
            } else {
                // cleanup old entries
                $tmpLog = array();
                foreach($log as $path => $data) {
                    if($data["timestamp"] > (time()-30)) { // remove 30 seconds old entries
                        $tmpLog[$path] = $data;
                    }
                }
            }
        }

        return $log;
    }

    /**
     * @param $log
     */
    public static function saveDeleteLog($log) {

        // cleanup old entries
        $tmpLog = array();
        foreach($log as $path => $data) {
            if($data["timestamp"] > (time()-30)) { // remove 30 seconds old entries
                $tmpLog[$path] = $data;
            }
        }

        \Pimcore\File::put(Asset\WebDAV\Service::getDeleteLogFile(), serialize($tmpLog));
    }
}
