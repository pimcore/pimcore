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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

include_once("pimcore/config/startup.php");

try {
    \Pimcore::run();
} catch (Exception $e) {
    // handle exceptions, log to file
    if(class_exists("Pimcore\\Logger")) {
        \Pimcore\Logger::emerg($e);
    }
    throw $e;
}
