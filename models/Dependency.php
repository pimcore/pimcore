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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

/**
 * @method Dependency\Dao getDao()
 * @method void save()
 */
class Dependency extends AbstractModel
{
    /**
     * The ID of the object to get dependencies for
     *
     * @var int
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
     * @var array
     */
    public $requires = [];

    /**
     * Static helper to get the dependencies for the given sourceId & type
     *
     * @param int $id
     * @param string $type
     *
     * @return Dependency
     */
    public static function getBySourceId($id, $type)
    {
        $d = new self();
        $d->getDao()->getBySourceId($id, $type);

        return $d;
    }

    /**
     * Add a requirement to the source object
     *
     * @param int $id
     * @param string $type
     */
    public function addRequirement($id, $type)
    {
        $this->requires[] = [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * Used when element gets deleted. Removes entries (by source = element) and
     * schedules a sanity check for the affected targets.
     *
     * @param Element\ElementInterface $element
     */
    public function cleanAllForElement($element)
    {
        $this->getDao()->cleanAllForElement($element);
    }

    /**
     * Cleanup the dependencies for current source id.
     * Can be used for updating the dependencies.
     */
    public function clean()
    {
        $this->requires = [];
        $this->getDao()->clear();
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array
     */
    public function getRequires($offset = null, $limit = null)
    {
        return array_slice($this->requires, $offset, $limit);
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array
     */
    public function getRequiredBy($offset = null, $limit = null)
    {
        return $this->getDao()->getRequiredBy($offset, $limit);
    }

    /**
     * @param string|null $orderBy
     * @param string|null $orderDirection
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array
     */
    public function getRequiredByWithPath($offset = null, $limit = null, $orderBy = null, $orderDirection = null)
    {
        return $this->getDao()->getRequiredByWithPath($offset, $limit, $orderBy, $orderDirection);
    }

    /**
     * @param int $sourceId
     *
     * @return $this
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @param array $requires
     *
     * @return $this
     */
    public function setRequires($requires)
    {
        $this->requires = $requires;

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
     *
     * @return $this
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * @return int
     */
    public function getRequiresTotalCount()
    {
        return count($this->requires);
    }

    /**
     * @return int
     */
    public function getRequiredByTotalCount()
    {
        return $this->getDao()->getRequiredByTotalCount();
    }

    /**
     * Check if the source object is required by an other object (an other object depends on this object)
     *
     * @return bool
     */
    public function isRequired()
    {
        if (is_array($this->getRequiredBy()) && $this->getRequiredByTotalCount() > 0) {
            return true;
        }

        return false;
    }
}
