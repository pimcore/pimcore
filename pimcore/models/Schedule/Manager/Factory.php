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
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Schedule\Manager;

use Pimcore\Model;

class Factory {
    /**
     * @static
     * @param  string $pidFile
     * @return Procedural|Daemon
     */
    public static function getManager($pidFile, $type = null) {

        // default manager, is always available
        $availableManagers = array("procedural");

        // check if pcntl is available
        if(function_exists("pcntl_fork") and function_exists("pcntl_waitpid") and function_exists("pcntl_wexitstatus") and function_exists("pcntl_signal")){
            $availableManagers[] = "daemon";
        }

        // force a specific type
        if(!in_array($type, $availableManagers)) {
            $type = "procedural";
        }

        if($type == "daemon") {
            \Logger::info("Using Schedule\\Manage\\_Daemon as maintenance manager");
            $manager = new Daemon($pidFile);
        } else {
            \Logger::info("Using Schedule\\Manager\\Procedural as maintenance manager");
            $manager = new Procedural($pidFile);
        }

        return $manager;
    }
}
