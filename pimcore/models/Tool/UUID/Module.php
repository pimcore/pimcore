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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model\Tool\UUID;

class Module extends \Pimcore\API\Module\AbstractModule {

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function init() {
        // attach event-listener
        foreach (["asset","object","document","object.class"] as $type) {
            \Pimcore::getEventManager()->attach($type . ".postAdd", array($this, "createUuid"));
            \Pimcore::getEventManager()->attach($type . ".postDelete", array($this, "deleteUuid"));
        }
    }

    /**
     * @param $e
     */
    public function createUuid($e){
        UUID::create($e->getTarget());
    }

    /**
     * @param $e
     */
    public function deleteUuid($e){
        $uuidObject = UUID::getByItem($e->getTarget());
        if($uuidObject instanceof UUID){
            $uuidObject->delete();
        }
    }
}
