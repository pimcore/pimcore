<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
