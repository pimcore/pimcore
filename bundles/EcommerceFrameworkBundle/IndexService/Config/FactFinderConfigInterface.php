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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFactFinder as DefaultFactFinderWorker;

/**
 * @deprecated since version 6.7.0 and will be removed in 7.0.0.
 *
 * Interface for IndexService Tenant Configurations using factfinder as index
 */
interface FactFinderConfigInterface extends ConfigInterface
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
     * @return array
     */
    public function getSubTenantCondition();

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return DefaultFactFinderWorker
     */
    public function getTenantWorker();
}

class_alias(FactFinderConfigInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IFactFinderConfig');
