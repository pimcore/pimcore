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
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Objectbrick\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class AbstractData extends Model\AbstractModel {

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var bool
     */
    public $doDelete;

    /**
     * @var Object\Concrete
     */
    public $object;


    /**
     * @param Object\Concrete $object
     */
    public function __construct(Object\Concrete $object) {
        $this->setObject($object);
    }

    /**
     * @return string
     */
    public function getFieldname () {
        return $this->fieldname;
    }

    /**
     * @param $fieldname
     * @return void
     */
    public function setFieldname ($fieldname) {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return 
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition () {
        $definition = Object\Objectbrick\Definition::getByKey($this->getType());
        return $definition;
    }

    /**
     * @param $doDelete
     * @return void
     */
    public function setDoDelete($doDelete)
    {
        $this->doDelete = $doDelete;
        return $this;
    }

    /**
     * @return bool
     */
    public function getDoDelete()
    {
        return $this->doDelete;
    }

    /**
     * @return Object\Concrete
     */
    public function getBaseObject() {
        return $this->getObject();
    }

    /**
     * @param $object
     * @return void
     */
    public function delete($object) {
        $this->doDelete = true;
        parent::delete($object);
    }

    /**
     * @return mixed
     */
    public function getValueFromParent($key) {

        $parent = Object\Service::hasInheritableParentObject($this->getObject());

        if(!empty($parent)) {
            $containerGetter = "get" . ucfirst($this->fieldname);
            $brickGetter = "get" . ucfirst($this->getType());
            $getter = "get" . ucfirst($key);

            if($parent->$containerGetter()->$brickGetter()) {
                return $parent->$containerGetter()->$brickGetter()->$getter();
            }

        }

        return null;
    }

    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return Object\Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $key
     * @return void
     */
    public function getValueForFieldName($key) {
        if ($this->$key) {
            return $this->$key;
        }
        return false;
    }

}
