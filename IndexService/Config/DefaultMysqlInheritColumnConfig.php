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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\DefaultMysqlInheritColumnConfig
 *
 * Sample implementation based on the \OnlineShop\Framework\IndexService\Config\DefaultMysql
 * that inherits attribute configuration of the default tenant.
 */
class DefaultMysqlInheritColumnConfig extends DefaultMysql {

    public function __construct($tenantConfig, $totalConfig = null) {
        $this->attributeConfig = $totalConfig->columns;

        $this->searchAttributeConfig = array();
        if($totalConfig->generalSearchColumns) {
            foreach($totalConfig->generalSearchColumns as $c) {
                $this->searchAttributeConfig[] = $c->name;
            }
        }
    }

    public function getTablename() {
        return "plugin_onlineshop_productindex3";
    }

    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations3";
    }
}