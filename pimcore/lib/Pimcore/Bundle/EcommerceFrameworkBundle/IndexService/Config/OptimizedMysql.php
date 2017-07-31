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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql as OptimizedMysqlWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Logger;

/**
 * Configuration for the optimized mysql product index implementation.
 */
class OptimizedMysql extends DefaultMysql implements IMockupConfig
{
    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     *
     * @return DefaultMockup
     */
    public function createMockupObject($objectId, $data, $relations)
    {
        return new DefaultMockup($objectId, $data, $relations);
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

    /**
     * @inheritDoc
     */
    public function setTenantWorker(IWorker $tenantWorker)
    {
        if (!$tenantWorker instanceof OptimizedMysql) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                OptimizedMysqlWorker::class
            ));
        }

        parent::setTenantWorker($tenantWorker);
    }
}
