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

namespace Pimcore\Controller\Action\Admin;

use Pimcore\Controller\Action\Admin;
use Pimcore\Model;

abstract class Element extends Admin {

    /**
     *
     */
    public function treeGetRootAction() {
        $type = $this->getParam("controller");
        $allowedTypes = ["asset","document","object"];

        $id = 1;
        if ($this->getParam("id")) {
            $id = intval($this->getParam("id"));
        }

        if(in_array($type, $allowedTypes)) {
            $root = Model\Element\Service::getElementById($type, $id);
            if ($root->isAllowed("list")) {
                $this->_helper->json($this->getTreeNodeConfig($root));
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    /**
     * @return array
     */
    protected function getTreeNodeConfig() {
        return [];
    }

    /**
     * @throws \Exception
     */
    public function getVersionsAction()
    {
        $id = intval($this->getParam("id"));
        $type = $this->getParam("controller");
        $allowedTypes = ["asset","document","object"];

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            if($element) {
                if($element->isAllowed("versions")) {

                    $schedule = $element->getScheduledTasks();
                    $schedules = array();
                    foreach ($schedule as $task){
                        if ($task->getActive()){
                            $schedules[$task->getVersion()] = $task->getDate();
                        }
                    }

                    $versions = $element->getVersions();
                    $versions = object2array($versions);
                    foreach ($versions as &$version){

                        unset($version["user"]["password"]); // remove password hash

                        $version["scheduled"] = null;
                        if(array_key_exists($version["id"], $schedules)) {
                            $version["scheduled"] = $schedules[$version["id"]];
                        }
                    }

                    $this->_helper->json(array("versions" => $versions));

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

        $this->_helper->json(array("success" => true));
    }

    /*
     *
     */
    public function getRequiresDependenciesAction()
    {
        $id = $this->getParam("id");
        $type = $this->getParam("controller");
        $allowedTypes = ["asset","document","object"];

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
        $allowedTypes = ["asset","document","object"];

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
        $properties = array();
        $type = $this->getParam("controller");
        $allowedTypes = ["asset","document","object"];

        if (in_array($type, $allowedTypes)) {
            $list = new Model\Property\Predefined\Listing();
            $list->setCondition("ctype = ?", [$type]);
            $list->setOrder("ASC");
            $list->setOrderKey("name");
            $list->load();

            foreach ($list->getProperties() as $type) {
                $properties[] = $type;
            }
        }

        $this->_helper->json(array("properties" => $properties));
    }
}
