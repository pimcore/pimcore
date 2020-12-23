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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;

/**
 *  Use this for ES Version >= 6
 *
 * @property ElasticSearch $tenantConfig
 */
class DefaultElasticSearch6 extends AbstractElasticSearch
{
    /**
     * name for routing param for ES bulk requests
     *
     * @var string
     */
    protected $routingParamName = '_routing';

    // type will be removed in ES 7
    protected function getMappingParams($type = null)
    {
        $params = [
            'index' => $this->getIndexNameVersion(),
            'type' => $this->tenantConfig->getElasticSearchClientParams()['indexType'],
            'body' => [
                'properties' => $this->createMappingAttributes(),
            ],
        ];

        return $params;
    }

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return ProductListInterface
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch6($this->tenantConfig);
    }
}
