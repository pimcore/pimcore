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

class Element_Sanitycheck extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @return int
     */
    public function getId(){
        return $this->id;
    }


    /**
     * @param  int $id
     * @return void
     */
    public function setId($id){
        $this->id = (int) $id;
    }

    /**
     * @return string
     */
    public function getType(){
        return $this->type;
    }

    /**
     * @param  string $type
     * @return void
     */
    public function setType($type){
        $this->type = $type;
    }


    /**
     * saves sanity check to db
     *
     * @return void
     */
    public function save(){
        $this->getResource()->save();
    }

    /**
     * deletes sanity check from db
     *
     * @return void
     */
    public function delete(){
        $this->getResource()->delete();
    }


    /**
     * @static
     * @return Element_Sanitycheck
     */
    public static function getNext(){
        $sanityCheck = new Element_Sanitycheck();
        $sanityCheck->getResource()->getNext();
        if($sanityCheck->getId() and $sanityCheck->getType()){
                return $sanityCheck;
        } else return null;


    }

}