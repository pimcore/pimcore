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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

class Checkbox extends Model\Document\Tag {

    /**
     * Contains the checkbox value
     *
     * @var boolean
     */
    public $value = false;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "checkbox";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return $this->value;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend() {
        return $this->value;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->value = $data;
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->value = $data;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return $this->value;
    }

    /**
     * @return boolean
     */
    public function isChecked() {
        return $this->isEmpty();
    }

    /**
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null){
       $data = $wsElement->value;
       if($data->bool === null or is_bool($data)){
            $this->value = (bool) $data->value;
       } else {
           throw new \Exception("cannot get values from web service import - invalid data");
       }
    }
}
