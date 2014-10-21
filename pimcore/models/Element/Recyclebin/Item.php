<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element\Recyclebin;

use Pimcore\Model;
use Pimcore\File; 
use Pimcore\Tool\Serialize;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Model\Element;

class Item extends Model\AbstractModel {

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
     * @param Element\ElementInterface $element
     * @param Model\User $user
     */
    public static function create (Element\ElementInterface $element, Model\User $user) {
        
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    /**
     * @static
     * @param $id
     * @return Element\Recyclebin\Item
     */
    public static function getById ($id) {
        
        $item = new self();
        $item->getResource()->getById($id);
        
        return $item;
    }

    /**
     *
     */
    public function restore () {
        
        $raw = file_get_contents($this->getStoreageFile());
        $element = Serialize::unserialize($raw);

        // check for element with the same name
        if($element instanceof Document) {
            $indentElement = Document::getByPath($element->getFullpath());
            if($indentElement) {
                $element->setKey($element->getKey()."_restore");
            }
        }
        else if ($element instanceof Asset) {
            $indentElement = Asset::getByPath($element->getFullpath());
            if($indentElement) {
                $element->setFilename($element->getFilename()."_restore");
            }
        }
        else if ($element instanceof Object\AbstractObject) {
            $indentElement = Object::getByPath($element->getFullpath());
            if($indentElement) {
                $element->setKey($element->getKey()."_restore");
            }
        }

        
        $this->restoreChilds($element);
        $this->delete();
    }

    /**
     * @param  User $user
     * @return void
     */
    public function save ($user=null) {

        if($this->getElement() instanceof Element\ElementInterface) {
            $this->setType(Element\Service::getElementType($this->getElement()));
        }

        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getFullPath());
        $this->setDate(time());

        $this->loadChilds($this->getElement());

        if($user instanceof Model\User){
            $this->setDeletedby($user->getName());
        }

        // serialize data
        Element\Service::loadAllFields($this->element);
        $this->element->_fulldump = true;
        $data = Serialize::serialize($this->getElement());
        
        $this->getResource()->save();
        
        if(!is_dir(PIMCORE_RECYCLEBIN_DIRECTORY)) {
            File::mkdir(PIMCORE_RECYCLEBIN_DIRECTORY);
        }

        File::put($this->getStoreageFile(), $data);

        $saveBinaryData = function ($element, $rec, $scope) {
            // assets are kina special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if($element instanceof Asset) {

                if($element->getType() != "folder") {
                    $handle = fopen($scope->getStorageFileBinary($element), "w+");
                    $src = $element->getStream();
                    stream_copy_to_stream($src, $handle);
                    fclose($handle);
                }

                $children = $element->getChilds();
                foreach ($children as $child) {
                    $rec($child, $rec, $scope);
                }
            }
        };

        $saveBinaryData($this->getElement(), $saveBinaryData, $this);

        @chmod($this->getStoreageFile(), File::getDefaultMode());
    }

    /**
     *
     */
    public function delete () {
        unlink($this->getStoreageFile());

        // remove binary files
        $files = glob(PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $this->getId() . "_*");
        if(is_array($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        $this->getResource()->delete();
    }

    /**
     * @param Element\ElementInterface $element
     */
    public function loadChilds (Element\ElementInterface $element) {
        
        $this->amount++;

        Element\Service::loadAllFields($element);

        // for all
        $element->getProperties();
        if(method_exists($element,"getScheduledTasks")) {
            $element->getScheduledTasks();
        }
        
        $element->_fulldump = true;
        
        if(method_exists($element,"getChilds")) {
            if($element instanceof Object\AbstractObject) {
                // because we also want variants
                $childs = $element->getChilds(array(Object::OBJECT_TYPE_FOLDER, Object::OBJECT_TYPE_VARIANT, Object::OBJECT_TYPE_OBJECT));
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
    public function restoreChilds (Element\ElementInterface $element) {


        $restoreBinaryData = function ($element, $scope) {
            // assets are kina special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if($element instanceof Asset) {
                $binFile = $scope->getStorageFileBinary($element);
                if(file_exists($binFile)) {
                    $binaryHandle = fopen($binFile, "r+");
                    $element->setStream($binaryHandle);
                }
            }
        };

        $restoreBinaryData($element, $this);

        $element->save();
        
        if(method_exists($element,"getChilds")) {
            if($element instanceof Object\AbstractObject) {
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
    public function getStoreageFile () {
        return PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $this->getId() . ".psf";
    }

    /**
     * @param $element
     * @return string
     */
    public function getStorageFileBinary($element) {
        return PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $this->getId() . "_" . Element\Service::getElementType($element) . "-" . $element->getId() . ".bin";
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId ($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath () {
        return $this->path;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath ($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType ($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype () {
        return $this->subtype;
    }

    /**
     * @param $subtype
     * @return $this
     */
    public function setSubtype ($subtype) {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount ()  {
        return $this->amount;
    }

    /**
     * @param $amount
     * @return $this
     */
    public function setAmount ($amount) {
        $this->amount = (int) $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getDate ()  {
        return $this->date;
    }

    /**
     * @param $date
     * @return $this
     */
    public function setDate ($date) {
        $this->date = (int) $date;
        return $this;
    }

    /**
     * @return Element\ElementInterface
     */
    public function getElement () {
        return $this->element;
    }

    /**
     * @param $element
     * @return $this
     */
    public function setElement ($element) {
        $this->element = $element;
        return $this;
    }

    /**
     * @param $username
     * @return $this
     */
    public function setDeletedby($username){
        $this->deletedby = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeletedby(){
        return $this->deletedby;
    }

}
