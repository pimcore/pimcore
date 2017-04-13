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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Objectbrick\Dao getDao()
 */
class Objectbrick extends Model\AbstractModel
{
    /**
     * @var array
     */
    public $items = [];

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var Concrete
     */
    public $object;

    /**
     * @var array
     */
    protected $brickGetters = [];

    /**
     * @param Concrete $object
     * @param string $fieldname
     */
    public function __construct($object, $fieldname)
    {
        $this->setObject($object);
        if ($fieldname) {
            $this->setFieldname($fieldname);
        }
    }

    /**
     * @param bool $withInheritedValues
     *
     * @return array
     */
    public function getItems($withInheritedValues = false)
    {
        if ($withInheritedValues) {
            $getters = $this->getBrickGetters();
            $values = [];
            foreach ($getters as $getter) {
                $value = $this->$getter();
                if (!empty($value)) {
                    $values[] = $value;
                }
            }

            return $values;
        } else {
            if (empty($this->items)) {
                foreach (get_object_vars($this) as $var) {
                    if ($var instanceof Objectbrick\Data\AbstractData) {
                        $this->items[] = $var;
                    }
                }
            }

            return $this->items;
        }
    }

    /**
     * @param $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

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
     * @return array
     */
    public function getBrickGetters()
    {
        $getters = [];
        foreach ($this->brickGetters as $bg) {
            $getters[] = 'get' . ucfirst($bg);
        }

        return $getters;
    }

    /**
     * @return array
     */
    public function getAllowedBrickTypes()
    {
        return is_array($this->brickGetters) ? $this->brickGetters : [];
    }

    /**
     * @return array
     */
    public function getItemDefinitions()
    {
        $definitions = [];
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }

        return $definitions;
    }

    /**
     * @param Concrete $object
     */
    public function save($object)
    {
        // set the current object again, this is necessary because the related object in $this->object can change (eg. clone & copy & paste, etc.)
        $this->setObject($object);

        $getters = $this->getBrickGetters();

        foreach ($getters as $getter) {
            $brick = $this->$getter();

            if ($brick instanceof Objectbrick\Data\AbstractData) {
                if ($brick->getDoDelete()) {
                    $brick->delete($object);

                    $setter = 's' . substr($getter, 1);
                    $this->$setter(null);

                    //check if parent object has brick, and if so, create an empty brick to enable inheritance
                    $parentBrick = null;
                    $inheritanceModeBackup = AbstractObject::getGetInheritedValues();
                    AbstractObject::setGetInheritedValues(true);
                    if (AbstractObject::doGetInheritedValues($object)) {
                        $container = $object->getValueFromParent($this->fieldname);
                        if (!empty($container)) {
                            $parentBrick = $container->$getter();
                        }
                    }
                    AbstractObject::setGetInheritedValues($inheritanceModeBackup);

                    if (!empty($parentBrick)) {
                        $brickType = '\\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object);
                        $this->$setter($brick);
                    }
                } else {
                    $brick->setFieldname($this->getFieldname());
                    $brick->save($object);
                }
            } else {
                if ($brick == null) {
                    $parentBrick = null;
                    $inheritanceModeBackup = AbstractObject::getGetInheritedValues();
                    AbstractObject::setGetInheritedValues(true);
                    if (AbstractObject::doGetInheritedValues($object)) {
                        $container = $object->getValueFromParent($this->fieldname);
                        if (!empty($container)) {
                            $parentBrick = $container->$getter();
                        }
                    }
                    AbstractObject::setGetInheritedValues($inheritanceModeBackup);

                    if (!empty($parentBrick)) {
                        $brickType = '\\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object);
                    }
                }
            }
        }
    }

    /**
     * @return AbstractObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param AbstractObject $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        // update all items with the new $object
        if (is_array($this->getItems())) {
            foreach ($this->getItems() as $brick) {
                if ($brick instanceof Objectbrick\Data\AbstractData) {
                    $brick->setObject($object);
                }
            }
        }

        return $this;
    }

    /**
     * @param Concrete $object
     */
    public function delete(Concrete $object)
    {
        if (is_array($this->getItems())) {
            foreach ($this->getItems() as $brick) {
                if ($brick instanceof Objectbrick\Data\AbstractData) {
                    $brick->delete($object);
                }
            }
        }

        $this->getDao()->delete($object);
    }
}
