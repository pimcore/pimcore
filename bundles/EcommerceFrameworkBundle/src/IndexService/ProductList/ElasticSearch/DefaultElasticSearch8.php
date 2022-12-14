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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch;

use Elastic\Elasticsearch\Client;

class DefaultElasticSearch8 extends AbstractElasticSearch
{
    /**
     * send a request to elasticsearch
     */
    protected function sendRequest(array $params): array
    {
        /**
         * @var Client $esClient
         */
        $esClient = $this->tenantConfig->getTenantWorker()->getElasticSearchClient();
        $result = [];

        if ($esClient instanceof Client) {
            if ($this->doScrollRequest) {
                $params = array_merge(['scroll' => $this->scrollRequestKeepAlive], $params);
                //kind of dirty hack :/
                $params['body']['size'] = $this->getLimit();
            }

            $result = $esClient->search($params)->asArray();

            if ($this->doScrollRequest) {
                $additionalHits = [];
                $scrollId = $result['_scroll_id'];

                while (true) {
                    $additionalResult = $esClient->scroll(['scroll_id' => $scrollId, 'scroll' => $this->scrollRequestKeepAlive])->asArray();

                    if (count($additionalResult['hits']['hits'])) {
                        $additionalHits = array_merge($additionalHits, $additionalResult['hits']['hits']);
                        $scrollId = $additionalResult['_scroll_id'];
                    } else {
                        break;
                    }
                }
                $result['hits']['hits'] = array_merge($result['hits']['hits'], $additionalHits);
            }
        }

        return $result;
    }
}
