<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * this file is included at end of startup and attaches event listeners for pimcore internal events
 */

// attach global shutdown event
Pimcore::getEventManager()->attach("system.shutdown", array("Pimcore", "shutdown"), 9999);

// remove assets on element delete
Pimcore::getEventManager()->attach("asset.postDelete", function (\Zend_EventManager_Event $e) {
    $asset = $e->getTarget();
    \Pimcore\Model\Element\Tag::setTagsForElement("asset", $asset->getId(), []);
}, 9999);

