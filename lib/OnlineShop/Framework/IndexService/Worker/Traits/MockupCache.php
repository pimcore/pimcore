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

namespace OnlineShop\Framework\IndexService\Worker\WorkerTraits;

/**
 * provides worker functionality for mockup cache and central store table
 *
 * Class \OnlineShop\Framework\IndexService\Worker\WorkerTraits\MockupCache
 */
trait MockupCache {


    /**
     * creates mockup cache key
     *
     * @param $objectId
     * @return string
     */
    protected function createMockupCacheKey($objectId) {
        return $this->getMockupCachePrefix() . "_" . $this->name . "_" . $objectId;
    }

    /**
     * deletes element from mockup cache
     *
     * @param $objectId
     */
    protected function deleteFromMockupCache($objectId) {
        $key = $this->getMockupCachePrefix() . "_" . $this->name . "_" . $objectId;
        \Pimcore\Model\Cache::remove($key);
    }

    /**
     * updates mockup cache, delegates creation of mockup object to tenant config
     *
     * @param $objectId
     * @param null $data
     * @return \OnlineShop\Framework\Model\DefaultMockup
     */
    public function saveToMockupCache($objectId, $data = null) {
        if(empty($data)) {
            $data = $this->db->fetchOne("SELECT data FROM " . $this->getStoreTableName() . " WHERE id = ? AND tenant = ?", array($objectId, $this->name));
            $data = json_decode($data, true);
        }

        $mockup = $this->tenantConfig->createMockupObject($objectId, $data['data'], $data['relations']);

        $key = $this->createMockupCacheKey($objectId);
        //use cache instance directly to aviod cache locking -> in this case force writing to cache is needed
        $cache = \Pimcore\Model\Cache::getInstance();
        $success = $cache->save(serialize($mockup), \Pimcore\Model\Cache::$cachePrefix . $key, [$this->getMockupCachePrefix()], null);
        $result = \Pimcore\Model\Cache::load($key);
        if($success && $result) {
            $this->db->query("UPDATE " . $this->getStoreTableName() . " SET crc_index = crc_current WHERE id = ? and tenant = ?", array($objectId, $this->name));
        } else {
            \Logger::err("Element with ID $objectId could not be added to mockup-cache");
        }

        return $mockup;
    }

    /**
     * gets mockup from cache and if not in cache, adds it to cache
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\DefaultMockup
     */
    public function getMockupFromCache($objectId) {
        $key = $this->createMockupCacheKey($objectId);
        $cachedItem = \Pimcore\Model\Cache::load($key);
        if($cachedItem) {
            $mockup = unserialize($cachedItem);
            if($mockup instanceof \OnlineShop\Framework\Model\DefaultMockup) {
                return $mockup;
            }
        }

        \Logger::info("Element with ID $objectId was not found in cache, trying to put it there.");
        return $this->saveToMockupCache($objectId);
    }

}
