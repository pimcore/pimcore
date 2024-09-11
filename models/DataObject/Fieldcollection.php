<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject;

use __PHP_Incomplete_Class;
use Exception;
use Iterator;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @template TItem of Model\DataObject\Fieldcollection\Data\AbstractData
 *
 * @method array{saveLocalizedRelations?: true, saveFieldcollectionRelations?: true} delete(Concrete $object, bool $saveMode = false)
 * @method Fieldcollection\Dao getDao()
 * @method TItem[] load(Concrete $object)
 */
class Fieldcollection extends Model\AbstractModel implements Iterator, DirtyIndicatorInterface, ObjectAwareFieldInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * @internal
     *
     * @var array<TItem|__PHP_Incomplete_Class>
     */
    protected array $items = [];

    /**
     * @internal
     *
     */
    protected string $fieldname;

    /**
     * @param TItem[] $items
     */
    public function __construct(array $items = [], string $fieldname = null)
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
     * @return TItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param TItem[] $items
     *
     * @return $this
     */
    public function setItems(array $items): static
    {
        $this->items = $items;
        $this->markFieldDirty('_self', true);

        return $this;
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): static
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    /**
     * @internal
     *
     * @return Fieldcollection\Definition[]
     */
    public function getItemDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws Exception
     */
    public function save(Concrete $object, array $params = []): void
    {
        $saveRelationalData = $this->getDao()->save($object, $params);

        /** @var Model\DataObject\ClassDefinition\Data\Fieldcollections $fieldDef */
        $fieldDef = $object->getClass()->getFieldDefinition($this->getFieldname());
        $allowedTypes = $fieldDef->getAllowedTypes();

        $collectionItems = $this->getItems();
        $index = 0;
        foreach ($collectionItems as $collection) {
            if ($collection instanceof Fieldcollection\Data\AbstractData) {
                if (in_array($collection->getType(), $allowedTypes)) {
                    $collection->setFieldname($this->getFieldname());
                    $collection->setIndex($index++);
                    $params['owner'] = $collection;

                    // set the current object again, this is necessary because the related object in $this->object can change (eg. clone & copy & paste, etc.)
                    $collection->setObject($object);
                    $collection->getDao()->save($object, $params, $saveRelationalData);
                } else {
                    throw new Exception('Fieldcollection of type ' . $collection->getType() . ' is not allowed in field: ' . $this->getFieldname());
                }
            }
        }
    }

    public function isEmpty(): bool
    {
        return count($this->getItems()) < 1;
    }

    public function add(Fieldcollection\Data\AbstractData $item): void
    {
        $this->items[] = $item;

        $this->markFieldDirty('_self', true);
    }

    public function remove(int $index): void
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);

            $this->markFieldDirty('_self', true);
        }
    }

    /**
     * @return TItem|null
     */
    public function get(int $index): ?Fieldcollection\Data\AbstractData
    {
        return $this->items[$index] ?? null;
    }

    /**
     * @return TItem|null
     */
    private function getByOriginalIndex(?int $index): ?Fieldcollection\Data\AbstractData
    {
        if ($index === null) {
            return null;
        }

        foreach ($this->items as $item) {
            if ($item->getIndex() === $index) {
                return $item;
            }
        }

        return null;
    }

    public function getCount(): int
    {
        return count($this->getItems());
    }

    /**
     * Methods for Iterator
     */
    public function rewind(): void
    {
        reset($this->items);
    }

    /**
     * @return TItem|false
     */
    public function current(): Fieldcollection\Data\AbstractData|false
    {
        return current($this->items);
    }

    public function key(): ?int
    {
        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    /**
     *
     * @throws Exception
     *
     * @internal
     */
    public function loadLazyField(Concrete $object, string $type, string $fcField, int $index, string $field): void
    {
        // lazy loading existing can be data if the item already had an index
        $item = $this->getByOriginalIndex($index);
        if ($item && !$item->isLazyKeyLoaded($field)) {
            if ($type == $item->getType()) {
                $fcDef = Model\DataObject\Fieldcollection\Definition::getByKey($type);
                /** @var Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface $fieldDef */
                $fieldDef = $fcDef->getFieldDefinition($field);

                $params = [
                    'context' => [
                        'object' => $object,
                        'containerType' => 'fieldcollection',
                        'containerKey' => $type,
                        'fieldname' => $fcField,
                        'index' => $index,
                    ], ];

                $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
                DataObject::disableDirtyDetection();

                $data = $fieldDef->load($item, $params);
                DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
                $item->setObjectVar($field, $data);
            }
            $item->markLazyKeyAsLoaded($field);
        }
    }

    protected function getObject(): ?Concrete
    {
        $this->rewind();
        $item = $this->current();
        if ($item instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            return $item->getObject();
        }

        return null;
    }

    public function setObject(?Concrete $object): static
    {
        // update all items with the new $object
        foreach ($this->getItems() as $item) {
            if ($item instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
                $item->setObject($object);
            }
        }

        return $this;
    }

    /**
     * @internal
     */
    public function loadLazyData(): void
    {
        $items = $this->getItems();
        /** @var Model\DataObject\Fieldcollection\Data\AbstractData $item */
        foreach ($items as $item) {
            $fcType = $item->getType();
            $fieldcolDef = Model\DataObject\Fieldcollection\Definition::getByKey($fcType);
            $fds = $fieldcolDef->getFieldDefinitions();
            foreach ($fds as $fd) {
                $fieldGetter = 'get' . ucfirst($fd->getName());
                $fieldValue = $item->$fieldGetter();
                if ($fieldValue instanceof Localizedfield) {
                    $fieldValue->loadLazyData();
                }
            }
        }
    }

    public function __wakeup(): void
    {
        foreach ($this->items as $key => $item) {
            if ($item instanceof __PHP_Incomplete_Class) {
                unset($this->items[$key]);
                Logger::error('fieldcollection item ' . $key . ' does not exist anymore');
            }
        }
    }
}
