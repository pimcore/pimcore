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

    public function init() {
        // attach event-listener
        foreach (["asset","object","document","object.class"] as $type) {
            Pimcore::getEventManager()->attach($type . ".postAdd", array($this, "createUuid"));
            Pimcore::getEventManager()->attach($type . ".postDelete", array($this, "deleteUuid"));
        }
    }

    public function createUuid($e){
        Tool_UUID::create($e->getTarget());
    }

    public function deleteUuid($e){
        $uuidObject = Tool_UUID::getByItem($e->getTarget());
        if($uuidObject instanceof Tool_UUID){
            $uuidObject->delete();
        }
    }
}
