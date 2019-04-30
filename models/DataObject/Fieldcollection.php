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
 * @package    DataObject\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Fieldcollection\Dao getDao()
 */
class Fieldcollection extends Model\AbstractModel implements \Iterator, DirtyIndicatorInterface
{
    use Model\DataObject\Traits\DirtyIndicatorTrait;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var
     */
    protected $fieldname;

    /**
     * @param array $items
     * @param null $fieldname
     */
    public function __construct($items = [], $fieldname = null)
    {
        if (!empty($items)) {
            $this->setItems($items);
        }
        if ($fieldname) {
            $this->setFieldname($fieldname);
        }

        $this->markFieldDirty('_self', true);
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param $items
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
     * @return
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
    public function getItemDefinitions()
    {
        $definitions = [];
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }

        return $definitions;
    }

    /**
     * @throws \Exception
     *
     * @param array $params
     * @param $object
     */
    public function save($object, $params = [])
    {
        $saveRelationalData = $this->getDao()->save($object, $params);

        $allowedTypes = $object->getClass()->getFieldDefinition($this->getFieldname())->getAllowedTypes();

        $collectionItems = $this->getItems();
        if (is_array($collectionItems)) {
            $index = 0;
            foreach ($collectionItems as $collection) {
                if ($collection instanceof Fieldcollection\Data\AbstractData) {
                    if (in_array($collection->getType(), $allowedTypes)) {
                        $collection->setFieldname($this->getFieldname());
                        $collection->setIndex($index++);

                        // set the current object again, this is necessary because the related object in $this->object can change (eg. clone & copy & paste, etc.)
                        $collection->setObject($object);
                        $collection->save($object, $params, $saveRelationalData);
                    } else {
                        throw new \Exception('Fieldcollection of type ' . $collection->getType() . ' is not allowed in field: ' . $this->getFieldname());
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (count($this->getItems()) < 1) {
            return true;
        }

        return false;
    }

    /**
     * @param $item
     */
    public function add($item)
    {
        $this->items[] = $item;

        $this->markFieldDirty('_self', true);
    }

    /**
     * @param $index
     */
    public function remove($index)
    {
        if ($this->items[$index]) {
            array_splice($this->items, $index, 1);

            $this->markFieldDirty('_self', true);
        }
    }

    /**
     * @param $index
     *
     * @return
     *
     * @todo: no return type definied here
     */
    public function get($index)
    {
        if ($this->items[$index]) {
            return $this->items[$index];
        }
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->getItems());
    }

    /**
     * Methods for Iterator
     */

    /*
     *
     */
    public function rewind()
    {
        reset($this->items);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $var = current($this->items);

        return $var;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $var = key($this->items);

        return $var;
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $var = next($this->items);

        return $var;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $var = $this->current() !== false;

        return $var;
    }

    /**
     * @param Concrete $object
     * @param $type
     * @param $fcField
     * @param $index
     * @param $field
     *
     * @throws \Exception
     */
    public function loadLazyField(Concrete $object, $type, $fcField, $index, $field)
    {
        /**
         * @var Model\DataObject\Fieldcollection\Data\AbstractData $item
         */
        $item = $this->get($index);
        if ($item && !$item->isLazyKeyLoaded($field)) {
            $fcDef = Model\DataObject\Fieldcollection\Definition::getByKey($type);
            /** @var $fieldDef Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface */
            $fieldDef = $fcDef->getFieldDefinition($field);

            $params = [
                'context' => [
                    'object' => $object,
                    'containerType' => 'fieldcollection',
                    'containerKey' => $type,
                    'fieldname' => $fcField,
                    'index' => $index
                ]];

            $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
            AbstractObject::disableDirtyDetection();

            $data = $fieldDef->load($item, $params);
            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
            $item->setObjectVar($field, $data);
            $item->markLazyKeyAsLoaded($field);
        }
    }

    /**
     * @return Concrete|null
     */
    protected function getObject(): ?Concrete
    {
        $this->rewind();
        $item = $this->current();
        if ($item instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            return $item->getObject();
        }

        return null;
    }
}
