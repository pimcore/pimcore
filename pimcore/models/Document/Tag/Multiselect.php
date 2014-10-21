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

class Multiselect extends Model\Document\Tag {

    /**
     * Contains the current selected values
     *
     * @var array
     */
    public $values = array();

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "multiselect";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return $this->values;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend() {
        return implode(",", $this->values);
    }

    public function getDataEditmode() {
        return implode(",", $this->values);
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->values = \Pimcore\Tool\Serialize::unserialize($data);
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->values = empty($data)?array():explode(",", $data);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->values);
    }

    /**
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->values === null or is_array($data->values)) {
            $this->values = $data->values;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }

    }

}
