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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Db\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *  Use this for ES Version >= 6
 *
 * @property ElasticSearch $tenantConfig
 *
 * @deprecated since version 6.9.0 and will be removed in 10.0.0.
 */
class DefaultElasticSearch6 extends AbstractElasticSearch
{
    /**
     * name for routing param for ES bulk requests
     *
     * @var string
     */
    protected $routingParamName = 'routing';

    public function __construct(ElasticSearchConfigInterface $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher, string $workerMode = null)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher, $workerMode);

        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.9.0 and will be removed in 10.0.0.',
            E_USER_DEPRECATED
        );
    }

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
