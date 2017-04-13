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
 * Interface for IndexService Tenant Configurations using elastic search as index
 *
 * Interface \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IElasticSearchConfig
 */
interface IElasticSearchConfig extends IConfig
{
    /**
     * returns elastic search client parameters defined in the tenant config
     *
     * @return array
     */
    public function getElasticSearchClientParams();

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition();

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch
     */
    public function getTenantWorker();
}
