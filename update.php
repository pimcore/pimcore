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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


if($revision == 18) {

    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE plugin_onlineshop_cartitem
      ADD comment LONGTEXT ASCII AFTER parentItemKey;
    ");
}

if($revision == 38) {

    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE plugin_onlineshop_cartitem
      ADD `addedDateTimestamp` int(10) NOT NULL AFTER comment;
    ");
}

if($revision == 47) {

    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE plugin_onlineshop_cart
      ADD `modificationDateTimestamp` int(10) NOT NULL AFTER creationDateTimestamp;
    ");
}