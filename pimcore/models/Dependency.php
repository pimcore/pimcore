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
 * @package    Dependency
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

class Dependency extends AbstractModel {

    /**
     * The ID of the object to get dependencies for
     *
     * @var integer
     */
    public $sourceId;

    /**
     * The type of the object to get dependencies for
     *
     * @var string
     */
    public $sourceType;

    /**
     * Contains the ID/type of objects which are required for the given source object (sourceId/sourceType)
     *
     * @var integer
     */
    public $requires = array();

    /**
     * Contains the ID/type of objects that need the given source object (sourceId/sourceType)
     *
     * @var integer
     */
    public $requiredBy = array();


    /**
     * Static helper to get the dependencies for the given sourceId & type
     *
     * @param integer $id
     * @param string $type
     * @return Dependency
     */
    public static function getBySourceId($id, $type) {

        $d = new self();
        $d->setSourceId($id);
        $d->setSourceType($type);
        $d->getResource()->getBySourceId();
        return $d;
    }

    /**
     * Add a requirement to the source object
     *
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function addRequirement($id, $type) {
        $this->requires[] = array(
            "type" => $type,
            "id" => $id
        );
    }

    /**
     * @param  Element\ELementInterface $element
     * @return void
     */
    public function cleanAllForElement($element){
        $this->getResource()->cleanAllForElement($element);
    }

    /**
     * Cleanup the dependencies for current source id
     *
     * @return void
     */
    public function clean() {
        $this->requires = array();
        $this->getResource()->clear();
    }

    /**
     * @return integer
     */
    public function getSourceId() {
        return $this->sourceId;
    }

    /**
     * @return array
     */
    public function getRequires() {
        return $this->requires;
    }

    /**
     * @return array
     */
    public function getRequiredBy() {
        return $this->requiredBy;
    }

    /**
     * @param integer $sourceId
     * @return void
     */
    public function setSourceId($sourceId) {
        $this->sourceId = (int) $sourceId;
        return $this;
    }

    /**
     * @param array $requires
     * @return void
     */
    public function setRequires($requires) {
        $this->requires = $requires;
        return $this;
    }

    /**
     * @param array $requiredBy
     * @return void
     */
    public function setRequiredBy($requiredBy) {
        $this->requiredBy = $requiredBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceType() {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     * @return void
     */
    public function setSourceType($sourceType) {
        $this->sourceType = $sourceType;
        return $this;
    }

    /**
     * Check if the source object is required by an other object (an other object depends on this object)
     *
     * @return void
     */
    public function isRequired() {
        if (is_array($this->getRequiredBy()) && count($this->getRequiredBy()) > 0) {
            return true;
        }
        return false;
    }


}
