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

namespace Pimcore\Controller\Action\Admin;

use Pimcore\Controller\Action\Admin;
use Pimcore\Model;

abstract class Element extends Admin
{

    /**
     *
     */
    public function treeGetRootAction()
    {
        $type = $this->getParam("controller");
        $allowedTypes = ["asset", "document", "object"];

        $id = 1;
        if ($this->getParam("id")) {
            $id = intval($this->getParam("id"));
        }

        if (in_array($type, $allowedTypes)) {
            $root = Model\Element\Service::getElementById($type, $id);
            if ($root->isAllowed("list")) {
                $this->_helper->json($this->getTreeNodeConfig($root));
            }
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    /**
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        return [];
    }

    /**
     * @throws \Exception
     */
    public function getVersionsAction()
    {
        $id = intval($this->getParam("id"));
        $type = $this->getParam("controller");
        $allowedTypes = ["asset", "document", "object"];

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            if ($element) {
                if ($element->isAllowed("versions")) {
                    $schedule = $element->getScheduledTasks();
                    $schedules = [];
                    foreach ($schedule as $task) {
                        if ($task->getActive()) {
                            $schedules[$task->getVersion()] = $task->getDate();
                        }
                    }

                    $versions = $element->getVersions();
                    $versions = Model\Element\Service::getSafeVersionInfo($versions);
                    foreach ($versions as &$version) {
                        $version["scheduled"] = null;
                        if (array_key_exists($version["id"], $schedules)) {
                            $version["scheduled"] = $schedules[$version["id"]];
                        }
                    }

                    $this->_helper->json(["versions" => $versions]);
                } else {
                    throw new \Exception("Permission denied, " . $type . " id [" . $id . "]");
                }
            } else {
                throw new \Exception($type . " with id [" . $id . "] doesn't exist");
            }
        }
    }

    /*
     *
     */
    public function deleteVersionAction()
    {
        $version = Model\Version::getById($this->getParam("id"));
        $version->delete();

        $this->_helper->json(["success" => true]);
    }

    /*
     *
     */
    public function getRequiresDependenciesAction()
    {
        $id = $this->getParam("id");
        $type = $this->getParam("controller");
        $allowedTypes = ["asset", "document", "object"];

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            if ($element instanceof Model\Element\ElementInterface) {
                $dependencies = Model\Element\Service::getRequiresDependenciesForFrontend($element->getDependencies());
                $this->_helper->json($dependencies);
            }
        }
        $this->_helper->json(false);
    }

    /**
     *
     */
    public function getRequiredByDependenciesAction()
    {
        $id = $this->getParam("id");
        $type = $this->getParam("controller");
        $allowedTypes = ["asset", "document", "object"];

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            if ($element instanceof Model\Element\ElementInterface) {
                $dependencies = Model\Element\Service::getRequiredByDependenciesForFrontend($element->getDependencies());
                $this->_helper->json($dependencies);
            }
        }
        $this->_helper->json(false);
    }

    /**
     *
     */
    public function getPredefinedPropertiesAction()
    {
        $properties = [];
        $type = $this->getParam("controller");
        $allowedTypes = ["asset", "document", "object"];

        if (in_array($type, $allowedTypes)) {
            $list = new Model\Property\Predefined\Listing();
            $list->setFilter(function ($row) use ($type) {
                if ($row["ctype"] == $type) {
                    return true;
                }

                return false;
            });

            $list->load();

            foreach ($list->getProperties() as $type) {
                $properties[] = $type;
            }
        }

        $this->_helper->json(["properties" => $properties]);
    }
}
