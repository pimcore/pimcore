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
 * Class \OnlineShop\Framework\IndexService\Config\OptimizedMysql
 *
 * Configuration for the optimized mysql product index implementation.
 */
class OptimizedMysql extends DefaultMysql implements IMockupConfig {

    /**
     * @var \OnlineShop\Framework\IndexService\Worker\DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Worker\OptimizedMysql
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new \OnlineShop\Framework\IndexService\Worker\OptimizedMysql($this);
        }
        return $this->tenantWorker;
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return \OnlineShop\Framework\Model\DefaultMockup
     */
    public function createMockupObject($objectId, $data, $relations) {
        return new \OnlineShop\Framework\Model\DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        $mockup = $this->getTenantWorker()->getMockupFromCache($objectId);

        if(empty($mockup)) {
            \Logger::warn("Could not load element with ID $objectId as mockup, loading complete object");
            return $this->getObjectById($objectId);
        } else {
            return $mockup;
        }

    }



}