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

namespace Pimcore\Model\DataObject;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @method \Pimcore\Model\DataObject\Objectbrick\Dao getDao()
 */
class Objectbrick extends Model\AbstractModel implements DirtyIndicatorInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var Model\DataObject\Concrete
     */
    protected $object;

    /**
     * @var int
     */
    protected $objectId;

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
                foreach ($this->getObjectVars() as $var) {
                    if ($var instanceof Objectbrick\Data\AbstractData) {
                        $this->items[] = $var;
                    }
                }
            }

            return $this->items;
        }
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        $this->markFieldDirty('_self', true);

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
     * @param string $fieldname
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
     * @param array $params
     */
    public function save($object, $params = [])
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
                        try {
                            $container = $object->getValueFromParent($this->fieldname);
                            if (!empty($container)) {
                                $parentBrick = $container->$getter();
                            }
                        } catch (InheritanceParentNotFoundException $e) {
                            // no data from parent available, continue ...
                        }
                    }
                    AbstractObject::setGetInheritedValues($inheritanceModeBackup);

                    if (!empty($parentBrick)) {
                        $brickType = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object, $params);
                        $this->$setter($brick);
                    }
                } else {
                    $brick->setFieldname($this->getFieldname());
                    $brick->save($object, $params);
                }
            } else {
                if ($brick == null) {
                    $parentBrick = null;
                    $inheritanceModeBackup = AbstractObject::getGetInheritedValues();
                    AbstractObject::setGetInheritedValues(true);
                    if (AbstractObject::doGetInheritedValues($object)) {
                        try {
                            $container = $object->getValueFromParent($this->fieldname);
                            if (!empty($container)) {
                                $parentBrick = $container->$getter();
                            }
                        } catch (InheritanceParentNotFoundException $e) {
                            // no data from parent available, continue ...
                        }
                    }
                    AbstractObject::setGetInheritedValues($inheritanceModeBackup);

                    if (!empty($parentBrick)) {
                        $brickType = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object, $params);
                    }
                }
            }
        }
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        if ($this->objectId && !$this->object) {
            $this->setObject(Concrete::getById($this->objectId));
        }

        return $this->object;
    }

    /**
     * @param Concrete $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->objectId = $object ? $object->getId() : null;
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

    public function __wakeup()
    {
        $brickGetter = null;

        // for backwards compatibility
        if (isset($this->object) && $this->object) {
            $this->objectId = $this->object->getId();
        }

        // sanity check, remove data requiring non-existing (deleted) brick definitions

        if (is_array($this->brickGetters)) {
            foreach ($this->brickGetters as $key => $brickGetter) {
                if (!property_exists($this, $brickGetter)) {
                    unset($this->brickGetters[$key]);
                    $this->$brickGetter = null;
                    Logger::error('brick ' . $brickGetter . ' does not exist anymore');
                }
            }
        }

        if (is_array($this->items)) {
            foreach ($this->items as $key => $item) {
                if ($item instanceof \__PHP_Incomplete_Class) {
                    unset($this->items[$key]);
                    Logger::error('brick ' . $brickGetter . ' does not exist anymore');
                }
            }
        }
    }

    /**
     * @param string $fieldName
     *
     * @return mixed
     */
    public function get($fieldName)
    {
        return $this->{'get'.ucfirst($fieldName)}();
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     *
     * @return mixed
     */
    public function set($fieldName, $value)
    {
        return $this->{'set'.ucfirst($fieldName)}($value);
    }

    /**
     * @internal
     *
     * @param string $brick
     * @param string $brickField
     * @param string $field
     *
     * @throws \Exception
     */
    public function loadLazyField($brick, $brickField, $field)
    {
        $item = $this->get($brick);
        if ($item && !$item->isLazyKeyLoaded($field)) {
            $brickDef = Model\DataObject\Objectbrick\Definition::getByKey($brick);
            /** @var Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface $fieldDef */
            $fieldDef = $brickDef->getFieldDefinition($field);
            $context = [];
            $context['object'] = $this->getObject();
            $context['containerType'] = 'objectbrick';
            $context['containerKey'] = $brick;
            $context['brickField'] = $brickField;
            $context['fieldname'] = $field;
            $params['context'] = $context;

            $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
            AbstractObject::disableDirtyDetection();
            $data = $fieldDef->load($this->$brick, $params);
            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

            $item->setObjectVar($field, $data);
            $item->markLazyKeyAsLoaded($field);
        }
    }

    /**
     * @internal
     */
    public function loadLazyData()
    {
        $allowedBrickTypes = $this->getAllowedBrickTypes();
        if (is_array($allowedBrickTypes)) {
            foreach ($allowedBrickTypes as $allowedBrickType) {
                $brickGetter = 'get' . ucfirst($allowedBrickType);
                $brickData = $this->$brickGetter();
                if ($brickData) {
                    $brickDef = Model\DataObject\Objectbrick\Definition::getByKey($allowedBrickType);
                    $fds = $brickDef->getFieldDefinitions();
                    foreach ($fds as $fd) {
                        $fieldGetter = 'get' . ucfirst($fd->getName());
                        $fieldValue = $brickData->$fieldGetter();
                        if ($fieldValue instanceof Localizedfield) {
                            $fieldValue->loadLazyData();
                        }
                    }
                }
            }
        }
    }
}
