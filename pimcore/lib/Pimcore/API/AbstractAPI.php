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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\API;

use Pimcore\Model\Schedule;

class AbstractAPI {

    protected static $legacyMappings = [
        "maintenance" => "system.maintenance",
        "maintenanceForce" => "system.maintenanceForce",
        "preDispatch" => "system.startup",
        "authenticateUser" => "admin.login.login.failed",
        "preLogoutUser" => "admin.login.logout",
        "preAddAsset" => "asset.preAdd",
        "postAddAsset" => "asset.postAdd",
        "preDeleteAsset" => "asset.preDelete",
        "postDeleteAsset" => "asset.postDelete",
        "preUpdateAsset" => "asset.preUpdate",
        "postUpdateAsset" => "asset.postUpdate",
        "preAddDocument" => "document.preAdd",
        "postAddDocument" => "document.postAdd",
        "preDeleteDocument" => "document.preDelete",
        "postDeleteDocument" => "document.postDelete",
        "preUpdateDocument" => "document.preUpdate",
        "postUpdateDocument" => "document.postUpdate",
        "preAddObject" => "object.preAdd",
        "postAddObject" => "object.postAdd",
        "preDeleteObject" => "object.preDelete",
        "postDeleteObject" => "object.postDelete",
        "preUpdateObject" => "object.preUpdate",
        "postUpdateObject" => "object.postUpdate",
        "preAddKeyValueKeyConfig" => "object.keyValue.keyConfig.preAdd",
        "postAddKeyValueKeyConfig" => "object.keyValue.keyConfig.postAdd",
        "preDeleteKeyValueKeyConfig" => "object.keyValue.keyConfig.preDelete",
        "postDeleteKeyValueKeyConfig" => "object.keyValue.keyConfig.postDelete",
        "preUpdateKeyValueKeyConfig" => "object.keyValue.keyConfig.preUpdate",
        "postUpdateKeyValueKeyConfig" => "object.keyValue.keyConfig.postUpdate",
        "preAddKeyValueGroupConfig" => "object.keyValue.groupConfig.preAdd",
        "postAddKeyValueGroupConfig" => "object.keyValue.groupConfig.postAdd",
        "preDeleteKeyValueGroupConfig" => "object.keyValue.groupConfig.preDelete",
        "postDeleteKeyValueGroupConfig" => "object.keyValue.groupConfig.postDelete",
        "preUpdateKeyValueGroupConfig" => "object.keyValue.groupConfig.preUpdate",
        "postUpdateKeyValueGroupConfig" => "object.keyValue.groupConfig.postUpdate",
        "preAddObjectClass" => "object.class.preAdd",
        "preUpdateObjectClass" => "object.class.preUpdate"
    ];

    /**
     *
     */
    public function init() {
        $this->registerLegacyEvents();
    }

    /**
     *
     */
    private function registerLegacyEvents() {

        $mappings = self::$legacyMappings;

        $eventManager = \Pimcore::getEventManager();
        $plugin = $this;
        $myMethods = get_class_methods($this);

        foreach ($myMethods as $method) {
            if(array_key_exists($method, $mappings)) {
                $event = $mappings[$method];

                if($method == "maintenanceForce") {
                    $eventManager->attach("system.maintenance", function ($e) use ($plugin) {
                        $e->getTarget()->registerJob(new Schedule\Maintenance\Job(get_class($plugin), $plugin, "maintenanceForce"), true);
                    });
                } else if (in_array($method, ["maintenance", "maintainance"])) {
                    $eventManager->attach("system.maintenance", function ($e) use ($plugin, $method) {
                        $e->getTarget()->registerJob(new Schedule\Maintenance\Job(get_class($plugin), $plugin, $method));
                    });
                } else if ($method == "authenticateUser") {
                    $eventManager->attach($event, function ($e) use ($plugin, $method) {
                        $user = $plugin->authenticateUser($e->getParam("username"), $e->getParam("password"));
                        if($user) {
                            $e->getTarget()->setUser($user);
                        }
                    });
                } else if ($method == "preLogoutUser") {
                    $eventManager->attach($event, function ($e) use ($plugin, $method) {
                        $plugin->preLogoutUser($e->getParam("user"));
                    });
                } else if (preg_match("/(pre|post)(update|add|delete)/i", $method)) {
                    // this is for Document/Asset/\Object\Abstract/\Object\ClassDefinition/...
                    $eventManager->attach($event, function ($e) use ($plugin, $method) {
                        $plugin->$method($e->getTarget());
                    });
                } else {
                    // for all events that don't have parameters or targets (eg. preDispatch/pimcore.startup)
                    $eventManager->attach($event, array($plugin, $method));
                }
            }
        }
    }
}
