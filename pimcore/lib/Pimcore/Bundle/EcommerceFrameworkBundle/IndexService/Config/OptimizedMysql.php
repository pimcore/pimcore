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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Logger;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql
 *
 * Configuration for the optimized mysql product index implementation.
 */
class OptimizedMysql extends DefaultMysql implements IMockupConfig
{
    /**
     * @var \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql
     */
    public function getTenantWorker()
    {
        if (empty($this->tenantWorker)) {
            $this->tenantWorker = new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql($this);
        }

        return $this->tenantWorker;
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup
     */
    public function createMockupObject($objectId, $data, $relations)
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     *
     * @return IIndexable | array
     */
    public function getObjectMockupById($objectId)
    {
        $mockup = $this->getTenantWorker()->getMockupFromCache($objectId);

        if (empty($mockup)) {
            Logger::warn("Could not load element with ID $objectId as mockup, loading complete object");

            return $this->getObjectById($objectId);
        } else {
            return $mockup;
        }
    }
}
