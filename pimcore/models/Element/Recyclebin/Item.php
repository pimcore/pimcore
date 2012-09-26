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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Element_Recyclebin_Item extends Pimcore_Model_Abstract {

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
     * @var Element_Interface
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
     * @param Element_Interface $element
     * @param User $user
     */
    public static function create (Element_Interface $element, User $user) {
        
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    /**
     * @static
     * @param $id
     * @return Element_Recyclebin_Item
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
        $element = Pimcore_Tool_Serialize::unserialize($raw);

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
        else if ($element instanceof Object_Abstract) {
            $indentElement = Object_Abstract::getByPath($element->getFullpath());
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
        
        if($this->getElement() instanceof Element_Interface) {
            $this->setType(Element_Service::getElementType($this->getElement()));
        }

        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getFullPath());
        $this->setDate(time());
        $this->loadChilds($this->getElement());
        if($user instanceof User){
            $this->setDeletedby($user->getName());
        }


        // serialize data
        $this->element->_fulldump = true;
        $data = Pimcore_Tool_Serialize::serialize($this->getElement());
        
        $this->getResource()->save();
        
        if(!is_dir(PIMCORE_RECYCLEBIN_DIRECTORY)) {
            mkdir(PIMCORE_RECYCLEBIN_DIRECTORY);
        }
        
        file_put_contents($this->getStoreageFile(),$data);
        chmod($this->getStoreageFile(), 0766);
    }

    /**
     *
     */
    public function delete () {
        unlink($this->getStoreageFile());
        $this->getResource()->delete();
    }

    /**
     * @param Element_Interface $element
     */
    public function loadChilds (Element_Interface $element) {
        
        $this->amount++;
        
        Element_Service::loadAllFields($element);
        
        // for all
        $element->getProperties();
        if(method_exists($element,"getScheduledTasks")) {
            $element->getScheduledTasks();
        }
        
        $element->_fulldump = true;
        
        if(method_exists($element,"getChilds")) {
            if($element instanceof Object_Abstract) {
                // because we also want variants
                $childs = $element->getChilds(array(Object_Abstract::OBJECT_TYPE_FOLDER, Object_Abstract::OBJECT_TYPE_VARIANT, Object_Abstract::OBJECT_TYPE_OBJECT));
            } else {
                $childs = $element->getChilds();
            }

            foreach ($childs as $child) {
                $this->loadChilds($child);
            }
        }
    }

    /**
     * @param Element_Interface $element
     */
    public function restoreChilds (Element_Interface $element) {
        $element->save();
        
        if(method_exists($element,"getChilds")) {
            if($element instanceof Object_Abstract) {
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
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId ($id) {
        $this->id = (int) $id;
    }

    /**
     * @return string
     */
    public function getPath () {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath ($path) {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @param $type
     */
    public function setType ($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSubtype () {
        return $this->subtype;
    }

    /**
     * @param $subtype
     */
    public function setSubtype ($subtype) {
        $this->subtype = $subtype;
    }

    /**
     * @return int
     */
    public function getAmount ()  {
        return $this->amount;
    }

    /**
     * @param $amount
     */
    public function setAmount ($amount) {
        $this->amount = (int) $amount;
    }

    /**
     * @return int
     */
    public function getDate ()  {
        return $this->date;
    }

    /**
     * @param $date
     */
    public function setDate ($date) {
        $this->date = (int) $date;
    }

    /**
     * @return Element_Interface
     */
    public function getElement () {
        return $this->element;
    }

    /**
     * @param $element
     */
    public function setElement ($element) {
        $this->element = $element;
    }

    /**
     * @param $username
     */
    public function setDeletedby($username){
        $this->deletedby = $username;
    }

    /**
     * @return string
     */
    public function getDeletedby(){
        return $this->deletedby;
    }

}
