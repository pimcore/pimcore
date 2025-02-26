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
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @method \Pimcore\Model\DataObject\Objectbrick\Dao getDao()
 */
class Objectbrick extends Model\AbstractModel implements DirtyIndicatorInterface, ObjectAwareFieldInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * @internal
     */
    protected array $items = [];

    /**
     * @internal
     *
     */
    protected string $fieldname;

    /**
     * @internal
     *
     */
    protected Concrete|Model\Element\ElementDescriptor|null $object = null;

    /**
     * @internal
     */
    protected ?int $objectId = null;

    /**
     * @internal
     *
     * @var array
     */
    protected $brickGetters = [];

    public function __construct(Concrete $object, string $fieldname)
    {
        $this->setObject($object);
        if ($fieldname) {
            $this->setFieldname($fieldname);
        }
    }

    public function getItems(bool $withInheritedValues = false): array
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
        }

        foreach ($this->getObjectVars() as $var) {
            if ($var instanceof Objectbrick\Data\AbstractData &&
                !in_array($var, $this->items, true)) {
                $this->items[] = $var;
            }
        }

        return $this->items;
    }

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

    public function getBrickGetters(): array
    {
        $getters = [];
        foreach ($this->brickGetters as $bg) {
            $getters[] = 'get' . ucfirst($bg);
        }

        return $getters;
    }

    public function getAllowedBrickTypes(): array
    {
        return $this->brickGetters;
    }

    public function getItemDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }

        return $definitions;
    }

    public function save(Concrete $object, array $params = []): void
    {
        // set the current object again, this is necessary because the related object in $this->object can change (eg. clone & copy & paste, etc.)
        $this->setObject($object);

        foreach ($this->getBrickGetters() as $getter) {
            $brick = $this->$getter(true);
            $createParentBrick = function () use ($object, $getter, $params) {
                //check if parent object has brick, and if so, create an empty brick to enable inheritance
                $parentBrick = DataObject\Service::useInheritedValues(true, function () use ($object, $getter) {
                    if (DataObject::doGetInheritedValues($object)) {
                        try {
                            return $object->getValueFromParent($this->fieldname)?->$getter();
                        } catch (InheritanceParentNotFoundException) {
                            return null;
                        }
                    }

                    return null;
                });

                if (!empty($parentBrick)) {
                    $brickType = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($parentBrick->getType());
                    $brick = new $brickType($object);
                    $brick->setFieldname($this->getFieldname());
                    $brick->save($object, $params);
                }

                return $parentBrick;
            };

            if ($brick instanceof Objectbrick\Data\AbstractData) {
                if ($brick->getDoDelete()) {
                    $brick->delete($object);

                    $setter = 's' . substr($getter, 1);
                    $this->$setter($createParentBrick());
                } else {
                    $brick->setFieldname($this->getFieldname());
                    $brick->save($object, $params);
                }
            } elseif ($brick === null) {
                $createParentBrick();
            }
        }
    }

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    public function setObject(?Concrete $object): static
    {
        $this->objectId = $object?->getId();
        $this->object = $object;

        // update all items with the new $object
        foreach ($this->getItems() as $brick) {
            if ($brick instanceof Objectbrick\Data\AbstractData) {
                $brick->setObject($object);
            }
        }

        return $this;
    }

    public function delete(Concrete $object): void
    {
        foreach ($this->getItems() as $brick) {
            if ($brick instanceof Objectbrick\Data\AbstractData) {
                $brick->delete($object);
            }
        }

        $this->getDao()->delete($object);
    }

    public function __sleep(): array
    {
        $finalVars = [];
        $blockedVars = ['object', 'brickGetters'];
        $vars = parent::__sleep();

        foreach ($vars as $value) {
            if (!in_array($value, $blockedVars)) {
                $finalVars[] = $value;
            }
        }

        return $finalVars;
    }

    public function __wakeup(): void
    {
        // for backwards compatibility
        if ($this->object) {
            $this->objectId = $this->object->getId();
        }

        // sanity check, remove data requiring non-existing (deleted) brick definitions
        foreach ($this->brickGetters as $key => $brickGetter) {
            if (!property_exists($this, $brickGetter)) {
                unset($this->brickGetters[$key]);
                $this->$brickGetter = null;
                Logger::error('brick ' . $brickGetter . ' does not exist anymore');
            }
        }

        foreach ($this->items as $key => $item) {
            if ($item instanceof __PHP_Incomplete_Class) {
                unset($this->items[$key]);
                Logger::error('brick item ' . $key . ' does not exist anymore');
            }
        }
    }

    public function get(string $fieldName): mixed
    {
        return $this->{'get'.ucfirst($fieldName)}();
    }

    public function set(string $fieldName, mixed $value): mixed
    {
        return $this->{'set'.ucfirst($fieldName)}($value);
    }

    /**
     *
     * @throws Exception
     *
     * @internal
     */
    public function loadLazyField(string $brick, string $brickField, string $field): void
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

            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
            DataObject::disableDirtyDetection();
            $data = $fieldDef->load($this->$brick, $params);
            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

            $item->setObjectVar($field, $data);
            $item->markLazyKeyAsLoaded($field);
        }
    }

    /**
     * @internal
     */
    public function loadLazyData(): void
    {
        $allowedBrickTypes = $this->getAllowedBrickTypes();
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
