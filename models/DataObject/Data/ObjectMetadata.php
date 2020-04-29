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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Data\ObjectMetadata\Dao getDao()
 */
class ObjectMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface
{
    use DataObject\Traits\OwnerAwareFieldTrait;

    /** @var DataObject\AbstractObject */
    protected $object;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string $fieldname
     * @param array $columns
     * @param null $object
     */
    public function __construct($fieldname, $columns = [], $object = null)
    {
        $this->fieldname = $fieldname;
        $this->columns = $columns;
        $this->setObject($object);
    }

    /**
     * @param DataObject\Concrete $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->markMeDirty();

        if (!$object) {
            $this->setObjectId(null);

            return $this;
        }

        $this->objectId = $object->getId();

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == 'get') {
            $key = substr($name, 3, strlen($name) - 3);

            $idx = array_searchi($key, $this->columns);
            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];

                return isset($this->data[$correctedKey]) ? $this->data[$correctedKey] : null;
            }

            throw new \Exception("Requested data $key not available");
        }

        if (substr($name, 0, 3) == 'set') {
            $key = substr($name, 3, strlen($name) - 3);
            $idx = array_searchi($key, $this->columns);

            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];
                $this->data[$correctedKey] = $arguments[0];
                $this->markMeDirty();
            } else {
                throw new \Exception("Requested data $key not available");
            }
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     */
    public function save($object, $ownertype, $ownername, $position, $index)
    {
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index);
    }

    /**
     * @param DataObject\Concrete $source
     * @param int $destinationId
     * @param string $fieldname
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     *
     * @return mixed
     */
    public function load(DataObject\Concrete $source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index)
    {
        return $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index);
    }

    /**
     * @param string $fieldname
     *
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @return DataObject\Concrete|null
     */
    public function getObject()
    {
        if ($this->getObjectId()) {
            $object = DataObject\Concrete::getById($this->getObjectId());
            if (!$object) {
                Logger::info('object ' . $this->getObjectId() . ' does not exist anymore');
            }

            return $object;
        }

        return null;
    }

    /**
     * @param DataObject\Concrete $element
     *
     * @return $this
     */
    public function setElement($element)
    {
        $this->markMeDirty();

        return $this->setObject($element);
    }

    /**
     * @return DataObject\Concrete|null
     */
    public function getElement()
    {
        return $this->getObject();
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getObject()->__toString();
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param int|null $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    public function __wakeup()
    {
        if ($this->object) {
            $this->objectId = $this->object->getId();
        }
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $finalVars = [];
        $blockedVars = ['object'];
        $vars = parent::__sleep();

        foreach ($vars as $value) {
            if (!in_array($value, $blockedVars)) {
                $finalVars[] = $value;
            }
        }

        return $finalVars;
    }
}
