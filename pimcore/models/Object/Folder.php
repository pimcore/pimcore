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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Folder extends Object_Abstract {

    /**
     * @var string
     */
    public $o_type = "folder";

    /**
     * @param array $values
     * @return Object_Folder
     */
    public static function create($values) {
        $object = new self();
        $object->setValues($values);

        $object->save();

        return $object;
    }

    /**
     * @return void
     */
    public function update() {

        parent::update();
        $this->getResource()->update();
        Pimcore_API_Plugin_Broker::getInstance()->postUpdateObject($this);
    }

    /**
     * @return void
     */
    public function delete() {

        if ($this->getO_Id() == 1) {
            throw new Exception("root-node cannot be deleted");
        }

        parent::delete();
    }


}
