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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlInheritColumnConfig
 *
 * Sample implementation based on the \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql
 * that inherits attribute configuration of the default tenant.
 */
class DefaultMysqlInheritColumnConfig extends DefaultMysql
{
    public function __construct($tenantConfig, $totalConfig = null)
    {
        $this->attributeConfig = $totalConfig->columns;

        $this->searchAttributeConfig = [];
        if ($totalConfig->generalSearchColumns) {
            foreach ($totalConfig->generalSearchColumns as $c) {
                $this->searchAttributeConfig[] = $c->name;
            }
        }
    }

    public function getTablename()
    {
        return "ecommerceframework_productindex3";
    }

    public function getRelationTablename()
    {
        return "ecommerceframework_productindex_relations3";
    }
}
