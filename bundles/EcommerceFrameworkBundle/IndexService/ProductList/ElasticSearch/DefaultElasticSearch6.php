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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;

/**
 * @deprecated since version 6.9.0 and will be removed in 10.0.0.
 */
class DefaultElasticSearch6 extends AbstractElasticSearch
{
    public function __construct(ElasticSearchConfigInterface $tenantConfig)
    {
        parent::__construct($tenantConfig);

        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.9.0 and will be removed in 10.0.0.',
            E_USER_DEPRECATED
        );
    }
}
