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
 * @category   Pimcore
 * @package    Dependency
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

/**
 * @method \Pimcore\Model\Dependency\Dao getDao()
 */
class Dependency extends AbstractModel
{

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
    public $requires = [];

    /**
     * Contains the ID/type of objects that need the given source object (sourceId/sourceType)
     *
     * @var integer
     */
    public $requiredBy = [];


    /**
     * Static helper to get the dependencies for the given sourceId & type
     *
     * @param integer $id
     * @param string $type
     * @return Dependency
     */
    public static function getBySourceId($id, $type)
    {
        $d = new self();
        $d->setSourceId($id);
        $d->setSourceType($type);
        $d->getDao()->getBySourceId();

        return $d;
    }

    /**
     * Add a requirement to the source object
     *
     * @param integer $id
     * @param string $type
     */
    public function addRequirement($id, $type)
    {
        $this->requires[] = [
            "type" => $type,
            "id" => $id
        ];
    }

    /**
     * @param  Element\ELementInterface $element
     */
    public function cleanAllForElement($element)
    {
        $this->getDao()->cleanAllForElement($element);
    }

    /**
     * Cleanup the dependencies for current source id
     */
    public function clean()
    {
        $this->requires = [];
        $this->getDao()->clear();
    }

    /**
     * @return integer
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @return array
     */
    public function getRequires()
    {
        return $this->requires;
    }

    /**
     * @return array
     */
    public function getRequiredBy()
    {
        return $this->requiredBy;
    }

    /**
     * @param integer $sourceId
     * @return $this
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @param array $requires
     * @return $this
     */
    public function setRequires($requires)
    {
        $this->requires = $requires;

        return $this;
    }

    /**
     * @param array $requiredBy
     * @return $this
     */
    public function setRequiredBy($requiredBy)
    {
        $this->requiredBy = $requiredBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     * @return $this
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * Check if the source object is required by an other object (an other object depends on this object)
     *
     * @return boolean
     */
    public function isRequired()
    {
        if (is_array($this->getRequiredBy()) && count($this->getRequiredBy()) > 0) {
            return true;
        }

        return false;
    }
}
