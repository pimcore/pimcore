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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Recyclebin;

use DeepCopy\TypeMatcher\TypeMatcher;
use Pimcore\Cache;
use Pimcore\File;
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

/**
 * @method \Pimcore\Model\Element\Recyclebin\Item\Dao getDao()
 */
class Item extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $subtype;

    /**
     * @var int
     */
    public $amount = 0;

    /**
     * @var Element\ElementInterface
     */
    public $element;

    /**
     * @var int
     */
    public $date;

    /**
     * @var string
     */
    public $deletedby;

    /**
     * @static
     *
     * @param Element\ElementInterface $element
     * @param Model\User $user
     */
    public static function create(Element\ElementInterface $element, Model\User $user = null)
    {
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    /**
     * @static
     *
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $item = new self();
            $item->getDao()->getById($id);

            return $item;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param Model\User|null $user
     *
     * @throws \Exception
     */
    public function restore($user = null)
    {
        $dummy = null;
        $raw = file_get_contents($this->getStoreageFile());
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
            $dummy = \Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
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
                throw new \Exception('Not sufficient permissions');
            }
        }

        try {
            $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
            AbstractObject::disableDirtyDetection();

            $this->doRecursiveRestore($element);

            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        } catch (\Exception $e) {
            Logger::error($e);
            if ($dummy) {
                $dummy->delete();
            }
            throw $e;
        }

        $this->delete();
    }

    /**
     * @param Model\User $user
     */
    public function save($user = null)
    {
        if ($this->getElement() instanceof Element\ElementInterface) {
            $this->setType(Element\Service::getElementType($this->getElement()));
        }

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

        if (!is_dir(PIMCORE_RECYCLEBIN_DIRECTORY)) {
            File::mkdir(PIMCORE_RECYCLEBIN_DIRECTORY);
        }

        File::put($this->getStoreageFile(), $data);

        $saveBinaryData = function ($element, $rec, $scope) {
            // assets are kind of special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                if ($element->getType() != 'folder') {
                    $handle = fopen($scope->getStorageFileBinary($element), 'w', false, File::getContext());
                    $src = $element->getStream();
                    stream_copy_to_stream($src, $handle);
                    fclose($handle);
                }

                if (method_exists($element, 'getChildren')) {
                    $children = $element->getChildren();
                    foreach ($children as $child) {
                        $rec($child, $rec, $scope);
                    }
                }
            }
        };

        $saveBinaryData($this->getElement(), $saveBinaryData, $this);

        @chmod($this->getStoreageFile(), File::getDefaultMode());
    }

    public function delete()
    {
        unlink($this->getStoreageFile());

        // remove binary files
        $files = glob(PIMCORE_RECYCLEBIN_DIRECTORY . '/' . $this->getId() . '_*');
        if (is_array($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        $this->getDao()->delete();
    }

    /**
     * @param Element\ElementInterface $element
     */
    public function loadChildren(Element\ElementInterface $element)
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
                $children = $element->getChildren([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT], true);
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
     * @param Element\ElementInterface $element
     *
     * @throws \Exception
     */
    protected function doRecursiveRestore(Element\ElementInterface $element)
    {
        $restoreBinaryData = function ($element, $scope) {
            // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                $binFile = $scope->getStorageFileBinary($element);
                if (file_exists($binFile)) {
                    $binaryHandle = fopen($binFile, 'r', false, File::getContext());
                    $element->setStream($binaryHandle);
                }
            }
        };

        $element = $this->unmarshalData($element);
        $restoreBinaryData($element, $this);

        if ($element instanceof DataObject\Concrete) {
            $element->markAllLazyLoadedKeysAsLoaded();
            $element->setOmitMandatoryCheck(true);
        }
        $element->save();

        if (method_exists($element, 'getChildren')) {
            if ($element instanceof DataObject\AbstractObject) {
                $children = $element->getChildren([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT], true);
            } elseif ($element instanceof Document) {
                $children = $element->getChildren(true);
            } else {
                $children = $element->getChildren();
            }
            if (is_array($children)) {
                foreach ($children as $child) {
                    $child->setParentId($element->getId());
                    $this->doRecursiveRestore($child);
                }
            }
        }
    }

    /**
     * @param Element\ElementInterface $data
     *
     * @return mixed
     */
    public function marshalData($data)
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
                    $elementType = Element\Service::getType($currentValue);
                    $descriptor = new Element\ElementDescriptor($elementType, $currentValue->getId());

                    return $descriptor;
                }
            ),
            new class($this->element) extends TypeMatcher {
                /**
                 * @param mixed $element
                 *
                 * @return bool
                 */
                public function matches($element)
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

    /**
     * @param Element\ElementInterface $data
     *
     * @return Element\ElementInterface
     */
    public function unmarshalData($data)
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

    /**
     * @return string
     */
    public function getStoreageFile()
    {
        return PIMCORE_RECYCLEBIN_DIRECTORY . '/' . $this->getId() . '.psf';
    }

    /**
     * @param Element\ElementInterface $element
     *
     * @return string
     */
    public function getStorageFileBinary($element)
    {
        return PIMCORE_RECYCLEBIN_DIRECTORY . '/' . $this->getId() . '_' . Element\Service::getElementType($element) . '-' . $element->getId() . '.bin';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param string $subtype
     *
     * @return $this
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = (int) $amount;

        return $this;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = (int) $date;

        return $this;
    }

    /**
     * @return Element\ElementInterface
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param Element\ElementInterface $element
     *
     * @return $this
     */
    public function setElement($element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setDeletedby($username)
    {
        $this->deletedby = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeletedby()
    {
        return $this->deletedby;
    }
}
