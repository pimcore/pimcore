<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
