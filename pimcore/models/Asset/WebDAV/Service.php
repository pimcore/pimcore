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
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\Model\Asset;

class Service
{

    /**
     * @return string
     */
    public static function getDeleteLogFile()
    {
        return PIMCORE_WEBDAV_TEMP . "/delete.dat";
    }

    /**
     * @return array|mixed
     */
    public static function getDeleteLog()
    {
        $log = [];
        if (file_exists(self::getDeleteLogFile())) {
            $log = unserialize(file_get_contents(self::getDeleteLogFile()));
            if (!is_array($log)) {
                $log = [];
            } else {
                // cleanup old entries
                $tmpLog = [];
                foreach ($log as $path => $data) {
                    if ($data["timestamp"] > (time()-30)) { // remove 30 seconds old entries
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
    public static function saveDeleteLog($log)
    {

        // cleanup old entries
        $tmpLog = [];
        foreach ($log as $path => $data) {
            if ($data["timestamp"] > (time()-30)) { // remove 30 seconds old entries
                $tmpLog[$path] = $data;
            }
        }

        \Pimcore\File::put(Asset\WebDAV\Service::getDeleteLogFile(), serialize($tmpLog));
    }
}
