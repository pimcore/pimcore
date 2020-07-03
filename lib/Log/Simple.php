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

namespace Pimcore\Log;

use Pimcore\File;

class Simple
{
    /**
     * @param string $name
     * @param string $message
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
                File::put($log, '');
            }

            $date = new \DateTime('now');

            $f = fopen($log, 'a+');
            fwrite($f, $date->format(\DateTime::ISO8601) . ' : ' . $message . "\n");
            fclose($f);
        }
    }
}
