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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_UUID_Module extends Pimcore_API_Module_Abstract{

    protected function createUuid($item){
        Tool_UUID::create($item);
    }

    protected function deleteUuid($item){
        $uuidObject = Tool_UUID::getByItem($item);
        if($uuidObject instanceof Tool_UUID){
            $uuidObject->delete();
        }
    }

    public function postAddObject($object){
        $this->createUuid($object);
    }

    public function postDeleteObject($object){
        $this->deleteUuid($object);
    }

    public function postAddAsset($asset) {
        $this->createUuid($asset);
    }

    public function postDeleteAsset($asset) {
        $this->deleteUuid($asset);
    }

    public function postAddDocument($document) {
        $this->createUuid($document);
    }

    public function postDeleteDocument($document){
        $this->deleteUuid($document);
    }
}
