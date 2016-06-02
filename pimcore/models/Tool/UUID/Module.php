<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model\Tool\UUID;

class Module extends \Pimcore\API\Module\AbstractModule
{

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function init()
    {
        // attach event-listener
        foreach (["asset", "object", "document", "object.class"] as $type) {
            \Pimcore::getEventManager()->attach($type . ".postAdd", [$this, "createUuid"]);
            \Pimcore::getEventManager()->attach($type . ".postDelete", [$this, "deleteUuid"]);
        }
    }

    /**
     * @param $e
     */
    public function createUuid($e)
    {
        UUID::create($e->getTarget());
    }

    /**
     * @param $e
     */
    public function deleteUuid($e)
    {
        $uuidObject = UUID::getByItem($e->getTarget());
        if ($uuidObject instanceof UUID) {
            $uuidObject->delete();
        }
    }
}
