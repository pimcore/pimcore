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

use Pimcore\Cache;
use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject;
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
    public static function create(Element\ElementInterface $element, Model\User $user)
    {
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    /**
     * @static
     *
     * @param $id
     *
     * @return Element\Recyclebin\Item
     */
    public static function getById($id)
    {
        $item = new self();
        $item->getDao()->getById($id);

        return $item;
    }

    /**
     * @param null $user
     *
     * @throws \Exception
     */
    public function restore($user = null)
    {
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
        }

        if (\Pimcore\Tool\Admin::getCurrentUser()) {
            $parent = $element->getParent();
            if (!$parent->isAllowed('publish')) {
                throw new \Exception('Not sufficient permissions');
            }
        }

        $this->restoreChilds($element);
        $this->delete();
    }

    /**
     * @param Model\User $user
     */
    public function save($user=null)
    {
        if ($this->getElement() instanceof Element\ElementInterface) {
            $this->setType(Element\Service::getElementType($this->getElement()));
        }

        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getRealFullPath());
        $this->setDate(time());

        $this->loadChilds($this->getElement());

        if ($user instanceof Model\User) {
            $this->setDeletedby($user->getName());
        }

        // serialize data
        Element\Service::loadAllFields($this->element);
        $data = Serialize::serialize($this->getElement());

        $this->getDao()->save();

        if (!is_dir(PIMCORE_RECYCLEBIN_DIRECTORY)) {
            File::mkdir(PIMCORE_RECYCLEBIN_DIRECTORY);
        }

        File::put($this->getStoreageFile(), $data);

        $saveBinaryData = function ($element, $rec, $scope) {
            // assets are kina special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                if ($element->getType() != 'folder') {
                    $handle = fopen($scope->getStorageFileBinary($element), 'w', false, File::getContext());
                    $src = $element->getStream();
                    stream_copy_to_stream($src, $handle);
                    fclose($handle);
                }

                $children = $element->getChildren();
                foreach ($children as $child) {
                    $rec($child, $rec, $scope);
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
    public function loadChilds(Element\ElementInterface $element)
    {
        $this->amount++;

        Element\Service::loadAllFields($element);

        // for all
        $element->getProperties();
        if (method_exists($element, 'getScheduledTasks')) {
            $element->getScheduledTasks();
        }

        $element->_fulldump = true;

        // we need to add the tag of each item to the cache cleared stack, so that the item doesn't gets into the cache
        // with the property _fulldump set, because this would cause major issues in wakeUp()
        Cache::addIgnoredTagOnSave($element->getCacheTag());

        if (method_exists($element, 'getChilds')) {
            if ($element instanceof DataObject\AbstractObject) {
                // because we also want variants
                $childs = $element->getChildren([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]);
            } else {
                $childs = $element->getChilds();
            }

            foreach ($childs as $child) {
                $this->loadChilds($child);
            }
        }
    }

    /**
     * @param Element\ElementInterface $element
     */
    public function restoreChilds(Element\ElementInterface $element)
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

        $restoreBinaryData($element, $this);

        if ($element instanceof DataObject\Concrete) {
            $element->setOmitMandatoryCheck(true);
        }
        $element->save();

        if (method_exists($element, 'getChilds')) {
            if ($element instanceof DataObject\AbstractObject) {
                // don't use the getter because this will return an empty array (variants are excluded by default)
                $childs = $element->o_childs;
            } else {
                $childs = $element->getChilds();
            }
            foreach ($childs as $child) {
                $this->restoreChilds($child);
            }
        }
    }

    /**
     * @return string
     */
    public function getStoreageFile()
    {
        return PIMCORE_RECYCLEBIN_DIRECTORY . '/' . $this->getId() . '.psf';
    }

    /**
     * @param $element
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
     * @param $id
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
     * @param $path
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
     * @param $type
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
     * @param $subtype
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
     * @param $amount
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
     * @param $date
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
     * @param $element
     *
     * @return $this
     */
    public function setElement($element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * @param $username
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
