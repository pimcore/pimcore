<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Log;

use Pimcore\File;

class Simple
{

    /**
     * @param $name
     * @param $message
     */
    public static function log($name, $message)
    {
        $log = PIMCORE_LOG_DIRECTORY . "/$name.log";
        if (!is_file($log)) {
            if (is_writable(dirname($log))) {
                File::put($log, "AUTOCREATE\n");
            }
        }

        if (is_writable($log)) {
            // check for big logfile, empty it if it's bigger than about 200M
            if (filesize($log) > 200000000) {
                File::put($log, "");
            }

            $date = new \DateTime("now");

            $f = fopen($log, "a+");
            fwrite($f, $date->format(\DateTime::ISO8601) . " : " . $message . "\n");
            fclose($f);
        }
    }
}
