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
class ObjectMetadata extends Model\AbstractModel
{
    /**
     * @var DataObject\Concrete
     */
    protected $object;

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
    public $data = [];

    /**
     * @param $fieldname
     * @param array $columns
     * @param null $object
     *
     * @throws \Exception
     */
    public function __construct($fieldname, $columns = [], $object = null)
    {
        $this->fieldname = $fieldname;
        $this->object = $object;
        $this->columns = $columns;
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
                return $this->data[$key];
            }

            throw new \Exception("Requested data $key not available");
        }

        if (substr($name, 0, 3) == 'set') {
            $key = strtolower(substr($name, 3, strlen($name) - 3));
            if (in_array($key, $this->columns)) {
                $this->data[$key] = $arguments[0];
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
     */
    public function save($object, $ownertype = 'object', $ownername, $position)
    {
        $this->getDao()->save($object, $ownertype, $ownername, $position);
    }

    /**
     * @param DataObject\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     *
     * @return mixed
     */
    public function load(DataObject\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position)
    {
        return $this->getDao()->load($source, $destination, $fieldname, $ownertype, $ownername, $position);
    }

    /**
     * @param $fieldname
     *
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;

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
     * @param $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return DataObject\Concrete
     */
    public function getObject()
    {
        return $this->object;
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
     * @return mixed
     */
    public function __toString()
    {
        return $this->getObject()->__toString();
    }
}
