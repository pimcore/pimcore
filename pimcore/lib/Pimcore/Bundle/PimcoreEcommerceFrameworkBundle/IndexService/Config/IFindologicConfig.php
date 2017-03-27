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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

/**
 * Interface for IndexService Tenant Configurations using factfinder as index
 *
 * Interface \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IFindologicConfig
 */
interface IFindologicConfig extends IConfig
{
    /**
     * returns factfinder client parameters defined in the tenant config
     *
     * @param string $setting
     *
     * @return array|string
     */
    public function getClientConfig($setting = null);

    /**
     * returns condition for current subtenant
     *
     * @return string
     */
    public function getSubTenantCondition();


    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\ElasticSearch
     */
    public function getTenantWorker();
}
