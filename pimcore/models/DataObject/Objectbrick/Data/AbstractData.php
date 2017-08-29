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
 * @package    DataObject\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Objectbrick\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Objectbrick\Data\Dao getDao()
 */
abstract class AbstractData extends Model\AbstractModel
{
    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var bool
     */
    public $doDelete;

    /**
     * @var DataObject\Concrete
     */
    public $object;

    /**
     * @param DataObject\Concrete $object
     */
    public function __construct(Object\Concrete $object)
    {
        $this->setObject($object);
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
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
     * @return
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition()
    {
        $definition = DataObject\Objectbrick\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param $doDelete
     *
     * @return $this
     */
    public function setDoDelete($doDelete)
    {
        $this->doDelete = $doDelete;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDoDelete()
    {
        return $this->doDelete;
    }

    /**
     * @return DataObject\Concrete
     */
    public function getBaseObject()
    {
        return $this->getObject();
    }

    /**
     * @param $object
     */
    public function delete($object)
    {
        $this->doDelete = true;
        $this->getDao()->delete($object);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getValueFromParent($key)
    {
        $parent = DataObject\Service::hasInheritableParentObject($this->getObject());

        if (!empty($parent)) {
            $containerGetter = 'get' . ucfirst($this->fieldname);
            $brickGetter = 'get' . ucfirst($this->getType());
            $getter = 'get' . ucfirst($key);

            if ($parent->$containerGetter()->$brickGetter()) {
                return $parent->$containerGetter()->$brickGetter()->$getter();
            }
        }

        return null;
    }

    /**
     * @param DataObject\Concrete $object
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
     * @param string $key
     *
     * @return mixed
     */
    public function getValueForFieldName($key)
    {
        if ($this->$key) {
            return $this->$key;
        }

        return false;
    }
}
