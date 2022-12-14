<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch;

/**
 *  Use this for ES Version = 8
 */
class DefaultElasticSearch8 extends AbstractElasticSearch
{
    public function getProductList(): \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch8
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch8($this->tenantConfig);
    }
}
