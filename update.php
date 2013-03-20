<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cfasching
 * Date: 21.11.11
 * Time: 14:16
 * To change this template use File | Settings | File Templates.
 */
 
if($revision == 18) {

    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE plugin_onlineshop_cartitem
      ADD comment LONGTEXT ASCII AFTER parentItemKey;
    ");
}

if($revision == 36) {

    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE plugin_onlineshop_cartitem
      ADD `addedDateTimestamp` int(10) NOT NULL AFTER comment;
    ");
}