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

class Document_Page_Targeting extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $documentId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var array
     */
    public $conditions = array();

    /**
     * @var Document_Page_Targeting_Actions
     */
    public $actions;


    /**
     * Static helper to retrieve an instance of Document_Page_Targeting by the given ID
     *
     * @param integer $id
     * @return Document_DocType
     */
    public static function getById($id) {

        $target = new self();
        $target->setId(intval($id));
        $target->getResource()->getById();

        return $target;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param \Document_Page_Targeting_Actions $actions
     */
    public function setActions($actions)
    {
        if(!$actions) {
            $actions = new Document_Page_Targeting_Actions();
        }
        $this->actions = $actions;
    }

    /**
     * @return \Document_Page_Targeting_Actions
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $conditions
     */
    public function setConditions($conditions)
    {
        if(!$conditions) {
            $conditions = array();
        }
        $this->conditions = $conditions;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }
}
