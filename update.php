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