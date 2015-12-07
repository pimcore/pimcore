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
 * Interface for IndexService Tenant Configurations using factfinder as index
 *
 * Interface \OnlineShop\Framework\IndexService\Config\IFindologicConfig
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
     * @return \OnlineShop\Framework\IndexService\Worker\ElasticSearch
     */
    public function getTenantWorker();

}