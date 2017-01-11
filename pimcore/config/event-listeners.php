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

// remove tags on asset delete
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

// backed search
foreach (["asset", "object", "document"] as $type) {
    \Pimcore::getEventManager()->attach($type . ".postAdd", ["Pimcore\\Search\\EventHandler", "postAddElement"]);
    \Pimcore::getEventManager()->attach($type . ".postUpdate", ["Pimcore\\Search\\EventHandler", "postUpdateElement"]);
    \Pimcore::getEventManager()->attach($type . ".preDelete", ["Pimcore\\Search\\EventHandler", "preDeleteElement"]);
}

// UUID
$conf = \Pimcore\Config::getSystemConfig();
if ($conf->general->instanceIdentifier) {
    foreach (["asset", "object", "document", "object.class"] as $type) {
        \Pimcore::getEventManager()->attach($type . ".postAdd", function ($e) {
            \Pimcore\Model\Tool\UUID::create($e->getTarget());
        });

        \Pimcore::getEventManager()->attach($type . ".postDelete", function ($e) {
            $uuidObject = \Pimcore\Model\Tool\UUID::getByItem($e->getTarget());
            if ($uuidObject instanceof \Pimcore\Model\Tool\UUID) {
                $uuidObject->delete();
            }
        });
    }
}

//assign tags after copying an element
Pimcore::getEventManager()->attach(["document.postCopy", "asset.postCopy", "object.postCopy"],
    function (\Zend_EventManager_Event $e) {
        $elementType = strtok($e->getName(), '.');
        /** @var \Pimcore\Model\Element\AbstractElement $copiedElement */
        $copiedElement = $e->getTarget();
        /** @var \Pimcore\Model\Element\AbstractElement $baseElement */
        $baseElement = $e->getParam('base_element');
        \Pimcore\Model\Element\Tag::setTagsForElement($elementType, $copiedElement->getId(),
            \Pimcore\Model\Element\Tag::getTagsForElement($elementType, $baseElement->getId()));
    }
);
