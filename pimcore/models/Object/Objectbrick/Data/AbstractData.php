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
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Objectbrick\Data;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Objectbrick\Data\Dao getDao()
 */
class AbstractData extends Model\AbstractModel
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
     * @var Model\Object\Concrete
     */
    public $object;


    /**
     * @param Model\Object\Concrete $object
     */
    public function __construct(\Pimcore\Model\Object\Concrete $object)
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
        $definition = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param $doDelete
     * @return $this
     */
    public function setDoDelete($doDelete)
    {
        $this->flushContainer();
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
     * @return Model\Object\Concrete
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
        $this->flushContainer();
    }
    
    /**
     * Flushes the already collected items of the container object
     */
    protected function flushContainer()
    {
        $object = $this->getObject();
        if ($object) {
            $containerGetter = "get" . ucfirst($this->fieldname);

            $container = $object->$containerGetter();
            if ($container instanceof \Pimcore\Model\Object\Objectbrick) {
                $container->setItems([]);
            }
        }
    }
    

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getValueFromParent($key)
    {
        $parent = \Pimcore\Model\Object\Service::hasInheritableParentObject($this->getObject());

        if (!empty($parent)) {
            $containerGetter = "get" . ucfirst($this->fieldname);
            $brickGetter = "get" . ucfirst($this->getType());
            $getter = "get" . ucfirst($key);

            if ($parent->$containerGetter()->$brickGetter()) {
                return $parent->$containerGetter()->$brickGetter()->$getter();
            }
        }

        return null;
    }

    /**
     * @param Model\Object\Concrete $object
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return Model\Object\Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $key
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
