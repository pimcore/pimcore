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

class Document_DocType extends Pimcore_Model_Abstract {

    /**
     * ID of the document-type
     *
     * @var integer
     */
    public $id;

    /**
     * Name of the document-type
     *
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $module;

    /**
     * The specified controller
     *
     * @var string
     */
    public $controller;

    /**
     * The specified action
     *
     * @var string
     */
    public $action;

    /**
     * The specified template
     *
     * @var string
     */
    public $template;

    /**
     * Type, must be one of the following: page,snippet,email
     *
     * @var string
     */
    public $type;

    /**
     * @var integer
     */
    public $priority = 0;


    /**
     * Static helper to retrieve an instance of Document_DocType by the given ID
     *
     * @param integer $id
     * @return Document_DocType
     */
    public static function getById($id) {

        $docType = new self();
        $docType->setId(intval($id));
        $docType->getResource()->getById();

        return $docType;
    }

    /**
     * Shortcut to quickly create a new instance
     *
     * @return Document_DocType
     */
    public static function create() {
        $type = new self();
        $type->save();

        return $type;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
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
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $template
     * @return void
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }


     /**
     * @param integer $priority
     * @return void
     */
    public function setPriority($priority) {
        $this->priority = (int) $priority;
    }

    /**
     * @return integer
     */
    public function getPriority() {
        return $this->priority;
    }

    /**
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }
}
