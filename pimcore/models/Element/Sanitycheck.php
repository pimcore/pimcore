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

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Sanitycheck extends Model\AbstractModel {

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
        return $this;
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
        return $this;
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
     * @return Sanitycheck
     */
    public static function getNext(){
        $sanityCheck = new Sanitycheck();
        $sanityCheck->getResource()->getNext();
        if($sanityCheck->getId() and $sanityCheck->getType()){
                return $sanityCheck;
        } else return null;
    }
}