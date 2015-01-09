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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Config;
use Pimcore\Model\Document;

abstract class PageSnippet extends Model\Document {

    /**
     * @var string
     */
    public $module;

    /**
     * @var string
     */
    public $controller = "default";

    /**
     * @var string
     */
    public $action = "default";

    /**
     * @var string
     */
    public $template;

    /**
     * Contains all content-elements of the document
     *
     * @var array
     */
    public $elements = null;

    /**
     * Contains all versions of the document
     *
     * @var array
     */
    public $versions = null;

    /**
     * Contains all scheduled tasks
     *
     * @var array
     */
    public $scheduledTasks = null;

    /**
     * @var null|int
     */
    public $contentMasterDocumentId;

    /**
     * @var array
     */
    protected $inheritedElements = array();

    /**
     * @see Document::update
     * @return void
     */
    protected function update() {

        // update elements
        $this->getElements();
        $this->getResource()->deleteAllElements();

        if (is_array($this->getElements()) and count($this->getElements()) > 0) {
            foreach ($this->getElements() as $name => $element) {
                if(!$element->getInherited()) {
                    $element->setResource(null);
                    $element->setDocumentId($this->getId());
                    $element->save();
                }
            }
        }

        // scheduled tasks are saved in $this->saveVersion();

        // load data which must be requested
        $this->getProperties();
        $this->getElements();

        // update this
        parent::update();

        // save version if needed
        $this->saveVersion(false, false);
    }

    /**
     * Save the current object as version
     *
     * @return void
     */
    public function saveVersion($setModificationDate = true, $callPluginHook = true) {

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("document.preUpdate", $this);
        }

        // set date
        if ($setModificationDate) {
            $this->setModificationDate(time());
        }

        // scheduled tasks are saved always, they are not versioned!
        $this->saveScheduledTasks();

        // create version
        $version = null;

        // only create a new version if there is at least 1 allowed
        if(Config::getSystemConfig()->documents->versions->steps
            || Config::getSystemConfig()->documents->versions->days) {
            $version = new Model\Version();
            $version->setCid($this->getId());
            $version->setCtype("document");
            $version->setDate($this->getModificationDate());
            $version->setUserId($this->getUserModification());
            $version->setData($this);
            $version->save();
        }

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("document.postUpdate", $this);
        }

        return $version;
    }

    /**
     * @see Document::delete
     * @return void
     */
    public function delete() {
        $versions = $this->getVersions();
        foreach ($versions as $version) {
            $version->delete();
        }

        // remove all tasks
        $this->getResource()->deleteAllTasks();

        parent::delete();
    }

    /**
     * Resolves dependencies and create tags for caching out of them
     *
     * @return array
     */
    public function getCacheTags($tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        $tags = parent::getCacheTags($tags);

        foreach ($this->getElements() as $element) {
            $tags = $element->getCacheTags($this, $tags);
        }

        return $tags;
    }

    /**
     * @see Document::resolveDependencies
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = parent::resolveDependencies();

        foreach ($this->getElements() as $element) {
            $dependencies = array_merge($dependencies, $element->resolveDependencies());
        }

        if($this->getContentMasterDocument() instanceof Document) {
            $key = "document_" . $this->getContentMasterDocument()->getId();
            $dependencies[$key] = array(
                "id" => $this->getContentMasterDocument()->getId(),
                "type" => "document"
            );
        }

        return $dependencies;
    }

    /**
     * @return string
     */
    public function getAction() {
        if (empty($this->action)) {
            return "default";
        }
        return $this->action;
    }

    /**
     * @return string
     */
    public function getController() {
        if (empty($this->controller)) {
            return "default";
        }
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * @param string $action
     * @return void
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @param string $controller
     * @return void
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @param string $template
     * @return void
     */
    public function setTemplate($template) {
        $this->template = $template;
        return $this;
    }

    /**
     * @param $module
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set raw data of an element (eg. for editmode)
     *
     * @param string $name
     * @param string $type
     * @param string $data
     * @return void
     */
    public function setRawElement($name, $type, $data) {
        try {
            if ($type) {
                $class = "\\Pimcore\\Model\\Document\\Tag\\" . ucfirst($type);

                // this is the fallback for custom document tags using prefixes
                // so we need to check if the class exists first
                if(!\Pimcore\Tool::classExists($class)) {
                    $oldStyleClass = "\\Document_Tag_" . ucfirst($type);
                    if(\Pimcore\Tool::classExists($oldStyleClass)) {
                        $class = $oldStyleClass;
                    }
                }

                $this->elements[$name] = new $class();
                $this->elements[$name]->setDataFromEditmode($data);
                $this->elements[$name]->setName($name);
                $this->elements[$name]->setDocumentId($this->getId());
            }
        }
        catch (\Exception $e) {
            \Logger::warning("can't set element " . $name . " with the type " . $type . " to the document: " . $this->getRealFullPath());
        }
        return $this;
    }

    /**
     * Set an element with the given key/name
     *
     * @param string $name
     * @param string $data
     * @return void
     */
    public function setElement($name, $data) {
        $this->elements[$name] = $data;
        return $this;
    }


    /**
     * @param $name
     * @return $this
     */
    public function removeElement($name) {

        if($this->hasElement($name)) {
            unset($this->elements[$name]);
        }

        return $this;
    }

    /**
     * Get an element with the given key/name
     *
     * @param string $name
     * @return Document\Tag
     */
    public function getElement($name) {
        $elements = $this->getElements();
        if($this->hasElement($name)) {
            return $elements[$name];
        } else {

            if(array_key_exists($name, $this->inheritedElements)) {
               return $this->inheritedElements[$name];
            }

            // check for content master document (inherit data)
            if($contentMasterDocument = $this->getContentMasterDocument()) {
                if($contentMasterDocument instanceof Document\PageSnippet) {
                    $inheritedElement = $contentMasterDocument->getElement($name);
                    if($inheritedElement) {
                        $inheritedElement = clone $inheritedElement;
                        $inheritedElement->setInherited(true);
                        $this->inheritedElements[$name] = $inheritedElement;
                        return $inheritedElement;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param int|null $contentMasterDocumentId
     */
    public function setContentMasterDocumentId($contentMasterDocumentId)
    {
        // this is that the path is automatically converted to ID => when setting directly from admin UI
        if (!is_numeric($contentMasterDocumentId) && !empty($contentMasterDocumentId)) {
            $contentMasterDocument = Document::getByPath($contentMasterDocumentId);
            if($contentMasterDocument instanceof Document\PageSnippet) {
                $contentMasterDocumentId = $contentMasterDocument->getId();
            }
        }

        if(empty($contentMasterDocumentId)) {
            $contentMasterDocument = null;
        }

        $this->contentMasterDocumentId = $contentMasterDocumentId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getContentMasterDocumentId()
    {
        return $this->contentMasterDocumentId;
    }

    /**
     * @return Document
     */
    public function getContentMasterDocument() {
        return Document::getById($this->getContentMasterDocumentId());
    }

    /**
     * @param $document
     * @return $this
     */
    public function setContentMasterDocument($document) {
        if($document instanceof Document\PageSnippet) {
            $this->setContentMasterDocumentId($document->getId());
        } else {
            $this->setContentMasterDocumentId(null);
        }
        return $this;
    }

    /**
     * @param  $name
     * @return bool
     */
    public function hasElement ($name) {
        $elements = $this->getElements();
        return array_key_exists($name,$elements);
    }

    /**
     * @return array
     */
    public function getElements() {
        if ($this->elements === null) {
            $this->setElements($this->getResource()->getElements());
        }
        return $this->elements;
    }

    /**
     * @param array $elements
     * @return void
     */
    public function setElements($elements) {
        $this->elements = $elements;
        return $this;
    }

    /**
     * @return array
     */
    public function getVersions() {
        if ($this->versions === null) {
            $this->setVersions($this->getResource()->getVersions());
        }
        return $this->versions;
    }

    /**
     * @param array $versions
     * @return void
     */
    public function setVersions($versions) {
        $this->versions = $versions;
        return $this;
    }

    /**
     * @see Document::getFullPath
     * @return string
     */
    public function getHref() {
        return $this->getFullPath();
    }

    /**
     * @return the $scheduledTasks
     */
    public function getScheduledTasks() {
        if ($this->scheduledTasks === null) {
            $taskList = new Model\Schedule\Task\Listing();
            $taskList->setCondition("cid = ? AND ctype='document'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }
        return $this->scheduledTasks;
    }

    /**
     * @param $scheduledTasks
     * @return $this
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
        return $this;
    }

    /**
     *
     */
    public function saveScheduledTasks() {
        $scheduled_tasks = $this->getScheduledTasks();
        $this->getResource()->deleteAllTasks();

        if (is_array($scheduled_tasks) && count($scheduled_tasks) > 0) {
            foreach ($scheduled_tasks as $task) {
                $task->setId(null);
                $task->setResource(null);
                $task->setCid($this->getId());
                $task->setCtype("document");
                $task->save();
            }
        }
    }

    /**
     *
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();

        $blockedVars = array("inheritedElements");

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
}
