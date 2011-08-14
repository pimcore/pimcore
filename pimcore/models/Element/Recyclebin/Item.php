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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Element_Recyclebin_Item extends Pimcore_Model_Abstract {
    
    public $id;
    public $path;
    public $type;
    public $subtype;
    public $amount = 0;
    public $element;
    public $date;
    public $deletedby;
   
        
    public static function create (Element_Interface $element, User $user) {
        
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }
    
    public static function getById ($id) {
        
        $item = new self();
        $item->getResource()->getById($id);
        
        return $item;
    }
    
    
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
        
        if($this->getElement() instanceof Document) {
            $this->setType("document");
        }
        else if($this->getElement() instanceof Asset) {
            $this->setType("asset");
        }
        else if($this->getElement() instanceof Object_Abstract) {
            $this->setType("object");
        }
        
        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getFullPath());
        $this->setDate(time());
        $this->loadChilds($this->getElement());
        if($user instanceof User){
            $this->setDeletedby($user->getUsername());
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
    
    public function delete () {
        unlink($this->getStoreageFile());
        $this->getResource()->delete();
    }
    
    public function loadChilds (Element_Interface $element) {
        
        $this->amount++;
        
        if($element instanceof Document) {
            if($element instanceof Document_PageSnippet) {
                $element->getElements();
            }
        }
        else if ($element instanceof Asset) {
            if(!$element instanceof Asset_Folder) {
                $element->setData(null);
                $element->getData();
            }
        }
        else if ($element instanceof Object_Abstract) {

        }
        
        // for all
        $element->getProperties();
        $element->getPermissions();
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
    
    public function getStoreageFile () {
        return PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $this->getId() . ".psf";
    }
    
    public function getId() {
        return $this->id;
    }    
    
    public function setId ($id) {
        $this->id = (int) $id;
    }
    
    public function getPath () {
        return $this->path;
    }
    
    public function setPath ($path) {
        $this->path = $path;
    }
    
    public function getType () {
        return $this->type;
    }
    
    public function setType ($type) {
        $this->type = $type;
    }
    
    public function getSubtype () {
        return $this->subtype;
    }
    
    public function setSubtype ($subtype) {
        $this->subtype = $subtype;
    }
    
    public function getAmount ()  {
        return $this->amount;
    }
    
    public function setAmount ($amount) {
        $this->amount = (int) $amount;
    }
    
    public function getDate ()  {
        return $this->date;
    }
    
    public function setDate ($date) {
        $this->date = (int) $date;
    }
    
    public function getElement () {
        return $this->element;
    }
    
    public function setElement ($element) {
        $this->element = $element;
    }

    public function setDeletedby($username){
        $this->deletedby=$username;
    }

    public function getDeletedby(){
        return $this->deletedby;
    }

}
