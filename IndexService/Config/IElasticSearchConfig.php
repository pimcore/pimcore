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
 * Interface for IndexService Tenant Configurations using elastic search as index
 *
 * Interface \OnlineShop\Framework\IndexService\Config\IElasticSearchConfig
 */
interface IElasticSearchConfig extends IConfig {

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
     * @return \OnlineShop\Framework\IndexService\Worker\ElasticSearch
     */
    public function getTenantWorker();

}