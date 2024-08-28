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

namespace Pimcore\Model\Element\Recyclebin;

use DeepCopy\TypeMatcher\TypeMatcher;
use Exception;
use League\Flysystem\StorageAttributes;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Recyclebin\Item\Dao getDao()
 */
class Item extends Model\AbstractModel
{
    protected int $id;

    protected string $path;

    protected string $type;

    protected string $subtype;

    protected int $amount = 0;

    protected Element\ElementInterface $element;

    protected int $date;

    protected string $deletedby;

    public static function create(Element\ElementInterface $element, Model\User $user = null): void
    {
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    public static function getById(int $id): ?Item
    {
        try {
            $item = new self();
            $item->getDao()->getById($id);

            return $item;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function restore(Model\User $user = null): void
    {
        $dummy = null;
        $raw = Storage::get('recycle_bin')->read($this->getStorageFile());
        $element = Serialize::unserialize($raw);

        // check for element with the same name
        if ($element instanceof Document) {
            $indentElement = Document::getByPath($element->getRealFullPath());
            if ($indentElement) {
                $element->setKey($element->getKey().'_restore');
            }
        } elseif ($element instanceof Asset) {
            $indentElement = Asset::getByPath($element->getRealFullPath());
            if ($indentElement) {
                $element->setFilename($element->getFilename().'_restore');
            }
        } elseif ($element instanceof DataObject\AbstractObject) {
            $indentElement = DataObject::getByPath($element->getRealFullPath());
            if ($indentElement) {
                $element->setKey($element->getKey().'_restore');
            }

            // create an empty object first and clone it
            // see https://github.com/pimcore/pimcore/issues/4219
            Model\Version::disable();
            $className = get_class($element);
            /** @var Document|Asset|AbstractObject $dummy */
            $dummy = Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
            $dummy->setId($element->getId());
            $dummy->setParentId($element->getParentId() ?: 1);
            $dummy->setKey($element->getKey());
            if ($dummy instanceof DataObject\Concrete) {
                $dummy->setOmitMandatoryCheck(true);
            }
            $dummy->save(['isRecycleBinRestore' => true]);
            Model\Version::enable();
        }

        if (\Pimcore\Tool\Admin::getCurrentUser()) {
            $parent = $element->getParent();
            if ($parent && !$parent->isAllowed('publish')) {
                throw new Exception('Not sufficient permissions');
            }
        }

        try {
            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
            DataObject::disableDirtyDetection();

            $this->doRecursiveRestore($element);

            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        } catch (Exception $e) {
            Logger::error((string) $e);
            if ($dummy) {
                $dummy->delete();
            }

            throw $e;
        }

        $this->delete();
    }

    public function save(Model\User $user = null): void
    {
        $this->setType(Element\Service::getElementType($this->getElement()));
        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getRealFullPath());
        $this->setDate(time());

        $this->loadChildren($this->getElement());

        if ($user instanceof Model\User) {
            $this->setDeletedby($user->getName());
        }

        // serialize data
        Element\Service::loadAllFields($this->element);

        $condensedData = $this->marshalData($this->getElement());
        $data = Serialize::serialize($condensedData);

        $this->getDao()->save();

        $storage = Storage::get('recycle_bin');
        $storage->write($this->getStorageFile(), $data);

        $saveBinaryData = function ($element, $rec, self $scope) use ($storage) {
            // assets are kind of special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                if ($element->getType() != 'folder') {
                    $storage->writeStream($scope->getStorageFileBinary($element), $element->getStream());
                }

                $children = $element->getChildren();
                foreach ($children as $child) {
                    $rec($child, $rec, $scope);
                }
            }
        };

        $saveBinaryData($this->getElement(), $saveBinaryData, $this);
    }

    public function delete(): void
    {
        $storage = Storage::get('recycle_bin');
        $storage->delete($this->getStorageFile());

        $files = $storage->listContents($this->getType())->filter(function (StorageAttributes $item) {
            return (bool) strpos($item->path(), '/' . $this->getId() . '_');
        });

        /** @var StorageAttributes $item */
        foreach ($files as $item) {
            $storage->delete($item->path());
        }

        $this->getDao()->delete();
    }

    public function loadChildren(Element\ElementInterface $element): void
    {
        $this->amount++;

        Element\Service::loadAllFields($element);

        // for all
        $element->getProperties();
        $element->getScheduledTasks();

        if ($element instanceof Element\ElementDumpStateInterface) {
            $element->setInDumpState(true);
        }

        // we need to add the tag of each item to the cache cleared stack, so that the item doesn't gets into the cache
        // with the dump state set to true, because this would cause major issues in wakeUp()
        Cache::addIgnoredTagOnSave($element->getCacheTag());

        if (method_exists($element, 'getChildren')) {
            if ($element instanceof DataObject\AbstractObject) {
                // because we also want variants
                $children = $element->getChildren(DataObject::$types, true);
            } elseif ($element instanceof Document) {
                $children = $element->getChildren(true);
            } else {
                $children = $element->getChildren();
            }

            foreach ($children as $child) {
                $this->loadChildren($child);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function doRecursiveRestore(Element\ElementInterface $element): void
    {
        $storage = Storage::get('recycle_bin');
        $restoreBinaryData = function (Element\ElementInterface $element, self $scope) use ($storage) {
            // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                $binFile = $scope->getStorageFileBinary($element);
                if ($storage->fileExists($binFile)) {
                    $element->setStream($storage->readStream($binFile));
                }
            }
        };

        $element = $this->unmarshalData($element);
        $restoreBinaryData($element, $this);

        if ($element instanceof DataObject\Concrete) {
            $element->markAllLazyLoadedKeysAsLoaded();
            $element->setOmitMandatoryCheck(true);
        }
        $element->save(['isRecycleBinRestore' => true]);

        if (method_exists($element, 'getChildren')) {
            if ($element instanceof DataObject\AbstractObject) {
                $children = $element->getChildren(DataObject::$types, true);
            } elseif ($element instanceof Document) {
                $children = $element->getChildren(true);
            } else {
                $children = $element->getChildren();
            }
            foreach ($children as $child) {
                $child->setParentId($element->getId());
                $this->doRecursiveRestore($child);
            }
        }
    }

    public function marshalData(Element\ElementInterface $data): mixed
    {
        //for full dump of relation fields in container types
        $context = [
            'source' => __METHOD__,
            'default' => true,
        ];
        $copier = Element\Service::getDeepCopyInstance($data, $context);

        $copier->addTypeFilter(
            new \DeepCopy\TypeFilter\ReplaceFilter(
                function ($currentValue) {
                    $elementType = Element\Service::getElementType($currentValue);
                    $descriptor = new Element\ElementDescriptor($elementType, $currentValue->getId());

                    return $descriptor;
                }
            ),
            new class((string)$this->element) extends TypeMatcher {
                public function matches(mixed $element): bool
                {
                    //compress only elements with full_dump_state = false
                    return $element instanceof Element\ElementInterface && $element instanceof Element\ElementDumpStateInterface && !($element->isInDumpState());
                }
            }
        );

        //filter for marshaling custom data-types which implements CustomRecyclingMarshalInterface
        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomRecyclingMarshalInterface) {
                            return $fieldDefinition->marshalRecycleData($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ), new PimcoreClassDefinitionMatcher(Data\CustomRecyclingMarshalInterface::class)
            );
        }
        $copier->addFilter(new Model\Version\SetDumpStateFilter(true), new \DeepCopy\Matcher\PropertyMatcher(Element\ElementDumpStateInterface::class, Element\ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));

        return $copier->copy($data);
    }

    public function unmarshalData(Element\ElementInterface $data): Element\ElementInterface
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'unmarshal',
        ];
        $copier = Element\Service::getDeepCopyInstance($data, $context);

        //filter for unmarshaling custom data-types which implements CustomRecyclingMarshalInterface
        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomRecyclingMarshalInterface) {
                            return $fieldDefinition->unmarshalRecycleData($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ), new PimcoreClassDefinitionMatcher(Data\CustomRecyclingMarshalInterface::class)
            );
        }

        return $copier->copy($data);
    }

    public function getStorageFile(): string
    {
        return sprintf('%s/%s.psf', $this->getType(), $this->getId());
    }

    /**
     * @deprecated since pimcore 11.3 and will be removed in 12.0
     * @see Item::getStorageFile()
     */
    public function getStoreageFile(): string
    {
        return $this->getStorageFile();
    }

    protected function getStorageFileBinary(Element\ElementInterface $element): string
    {
        return sprintf('%s/%s_%s-%s.bin', $this->getType(), $this->getId(), Element\Service::getElementType($element), $element->getId());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    public function setSubtype(string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getElement(): Element\ElementInterface
    {
        return $this->element;
    }

    public function setElement(Element\ElementInterface $element): static
    {
        $this->element = $element;

        return $this;
    }

    public function setDeletedby(string $username): static
    {
        $this->deletedby = $username;

        return $this;
    }

    public function getDeletedby(): string
    {
        return $this->deletedby;
    }
}
