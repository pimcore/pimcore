<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
            \Pimcore::getEventManager()->attach($type . ".postAdd", array($this, "createUuid"));
            \Pimcore::getEventManager()->attach($type . ".postDelete", array($this, "deleteUuid"));
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
