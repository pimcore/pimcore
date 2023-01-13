<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractMockupCacheWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql as OptimizedMysqlWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup;

/**
 * Configuration for the optimized mysql product index implementation.
 */
class OptimizedMysql extends DefaultMysql implements MockupConfigInterface
{
    /**
     * creates object mockup for given data
     *
     * @param int $objectId
     * @param array $data
     * @param array $relations
     *
     * @return DefaultMockup
     */
    public function createMockupObject(int $objectId, array $data, array $relations): DefaultMockup
    {
        return new DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param int $objectId
     *
     * @return DefaultMockup
     */
    public function getObjectMockupById(int $objectId): DefaultMockup
    {
        /** @var AbstractMockupCacheWorker $worker */
        $worker = $this->getTenantWorker();

        return $worker->getMockupFromCache($objectId);
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantWorker(WorkerInterface $tenantWorker): void
    {
        if (!$tenantWorker instanceof OptimizedMysqlWorker) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                OptimizedMysqlWorker::class
            ));
        }

        $this->checkTenantWorker($tenantWorker);
        $this->tenantWorker = $tenantWorker;
    }
}
