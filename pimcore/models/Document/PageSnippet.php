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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_PageSnippet extends Document {

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
     * @see Document::update
     * @return void
     */
    public function update() {

        // update elements
        $this->getElements();
        $this->getResource()->deleteAllElements();

        if (is_array($this->getElements()) and count($this->getElements()) > 0) {
            foreach ($this->getElements() as $name => $element) {
                $element->setResource(null);
                $element->setDocumentId($this->getId());
                $element->save();
            }
        }

        // update scheduled tasks
        $this->saveScheduledTasks();

        // load data which must be requested
        $this->getProperties();
        $this->getElements();

        // update this
        parent::update();

        // save version if needed
        $this->saveVersion(false);
    }

    /**
     * Save the current object as version
     *
     * @return void
     */
    public function saveVersion($setModificationDate = true) {

        // set date
        if ($setModificationDate) {
            $this->setModificationDate(time());
        }
        
        // scheduled tasks are saved always, they are not versioned!
        $this->saveScheduledTasks();
        
        // create version
        $version = new Version();
        $version->setCid($this->getId());
        $version->setCtype("document");
        $version->setDate($this->getModificationDate());
        $version->setUserId($this->getUserModification());
        $version->setData($this);
        $version->save();
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
    }

    /**
     * @param string $controller
     * @return void
     */
    public function setController($controller) {
        $this->controller = $controller;
    }

    /**
     * @param string $template
     * @return void
     */
    public function setTemplate($template) {
        $this->template = $template;
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
                $class = "Document_Tag_" . ucfirst($type);
                $this->elements[$name] = new $class();
                $this->elements[$name]->setDataFromEditmode($data);
                $this->elements[$name]->setName($name);
                $this->elements[$name]->setDocumentId($this->getId());
            }
        }
        catch (Exception $e) {
            Logger::warning("can't set element " . $name . " with the type " . $type . " to the document: " . $this->getFullPath());
        }
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
    }


    /**
     * Get an element with the given key/name
     *
     * @param string $name
     * @return Document_Tag
     */
    public function getElement($name) {
        $elements = $this->getElements();
        if($this->hasElement($name)) {
            return $elements[$name];
        }
        return null;
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
        if ($this->scheduledTasks == null) {
            $taskList = new Schedule_Task_List();
            $taskList->setCondition("cid = ? AND ctype='document'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }
        return $this->scheduledTasks;
    }

    /**
     * @param $scheduledTasks the $scheduledTasks to set
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
    }
    
    
    public function saveScheduledTasks () {
        $this->getScheduledTasks();
        $this->getResource()->deleteAllTasks();

        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setResource(null);
                $task->setCid($this->getId());
                $task->setCtype("document");
                $task->save();
            }
        }
    }
}
