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


/**
 * this file is included at end of startup and attaches event listeners for pimcore internal events
 */

// attach global shutdown event
Pimcore::getEventManager()->attach("system.shutdown", ["Pimcore", "shutdown"], 9999);

// remove assets on element delete
Pimcore::getEventManager()->attach("asset.postDelete", function (\Zend_EventManager_Event $e) {
    $asset = $e->getTarget();
    \Pimcore\Model\Element\Tag::setTagsForElement("asset", $asset->getId(), []);
}, 9999);


// attach workflow events to event handler
Pimcore::getEventManager()->attach(
    ["object.postAdd", "document.postAdd", "asset.postAdd"],
    ["\\Pimcore\\WorkflowManagement\\EventHandler", "elementPostAdd"]
);

Pimcore::getEventManager()->attach(
    ["object.postDelete", "document.postDelete", "asset.postDelete"],
    ["\\Pimcore\\WorkflowManagement\\EventHandler", "elementPostDelete"]
);

\Pimcore::getEventManager()->attach(
    ["admin.object.get.preSendData", "admin.asset.get.preSendData", "admin.document.get.preSendData"],
    ["\\Pimcore\\WorkflowManagement\\EventHandler", "adminElementGetPreSendData"]
);