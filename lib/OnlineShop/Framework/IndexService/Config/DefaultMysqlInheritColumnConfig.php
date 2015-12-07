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

namespace OnlineShop\Framework\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\DefaultMysqlInheritColumnConfig
 *
 * Sample implementation based on the \OnlineShop\Framework\IndexService\Config\DefaultMysql
 * that inherits attribute configuration of the default tenant.
 */
class DefaultMysqlInheritColumnConfig extends DefaultMysql {

    public function __construct($tenantConfigXml, $totalConfigXml = null) {
        $this->attributeConfig = $totalConfigXml->columns;

        $this->searchAttributeConfig = array();
        if($totalConfigXml->generalSearchColumns->column) {
            foreach($totalConfigXml->generalSearchColumns->column as $c) {
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