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

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Data\ObjectMetadata\Dao getDao()
 */
class ObjectMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface
{
    use DataObject\Traits\OwnerAwareFieldTrait;

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
     * @return $this|void
     */
    public function setObject($object)
    {
        $this->markMeDirty();

        if (!$object) {
            $this->setObjectId(null);

            return;
        }

        $this->objectId = $object->getId();

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == 'get') {
            $key = strtolower(substr($name, 3, strlen($name) - 3));

            if (in_array($key, $this->columns)) {
                return isset($this->data[$key]) ? $this->data[$key] : null;
            }

            throw new \Exception("Requested data $key not available");
        }

        if (substr($name, 0, 3) == 'set') {
            $key = strtolower(substr($name, 3, strlen($name) - 3));
            if (in_array($key, $this->columns)) {
                $this->data[$key] = $arguments[0];
                $this->markMeDirty();
            } else {
                throw new \Exception("Requested data $key not available");
            }
        }
    }

    /**
     * @param $object
     * @param string $ownertype
     * @param $ownername
     * @param $position
     * @param $index
     */
    public function save($object, $ownertype = 'object', $ownername, $position, $index)
    {
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index);
    }

    /**
     * @param DataObject\Concrete $source
     * @param $destinationId
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $index
     *
     * @return mixed
     */
    public function load(DataObject\Concrete $source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index)
    {
        return $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index);
    }

    /**
     * @param $fieldname
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
     * @return DataObject\Concrete
     */
    public function getObject()
    {
        if ($this->getObjectId()) {
            $object = DataObject\Concrete::getById($this->getObjectId());
            if (!$object) {
                throw new \Exception('object '  . $this->getObjectId() . ' does not exist anymore');
            }

            return $object;
        }
    }

    /**
     * @param $element
     *
     * @return $this
     *
     * @internal param $object
     */
    public function setElement($element)
    {
        $this->markMeDirty();

        return $this->setObject($element);
    }

    /**
     * @return DataObject\Concrete
     */
    public function getElement()
    {
        return $this->getObject();
    }

    /**
     * @param $columns
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
     * @return mixed
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
}
