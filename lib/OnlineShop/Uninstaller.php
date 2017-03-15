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


namespace OnlineShop;

class Uninstaller {

    /**
     * uninstalls e-commerce framework
     */
    public static function uninstall() {
        $db = \Pimcore\Db::get();
        $db->query("DROP TABLE IF EXISTS `plugin_onlineshop_cart`");
        $db->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartcheckoutdata`");
        $db->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartitem`");
        $db->query("DROP TABLE IF EXISTS `plugin_customerdb_event_orderEvent`");
        $db->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_reservations`");
        $db->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_tokens`");
        $db->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_statistics`");
        $db->query("DROP TABLE IF EXISTS `plugin_onlineshop_pricing_rule`");

        //remove permissions
        $key = 'plugin_onlineshop_pricing_rules';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key) );

        $key = 'plugin_onlineshop_back-office_order';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key) );

    }

}