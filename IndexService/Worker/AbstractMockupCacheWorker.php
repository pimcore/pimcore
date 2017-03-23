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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IMockupConfig;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\DefaultMockup;
use Pimcore\Logger;


/**
 * Class AbstractMockupCacheWorker
 *
 * provides worker functionality for mockup cache and central store table
 *
 * @package OnlineShop\Framework\IndexService\Worker
 */
abstract class AbstractMockupCacheWorker extends AbstractBatchProcessingWorker {

    /**
     * returns prefix for cache key
     *
     * @return string
     */
    protected abstract function getMockupCachePrefix();

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
        \Pimcore\Cache::remove($key);
    }

    /**
     * updates mockup cache, delegates creation of mockup object to tenant config
     *
     * @param $objectId
     * @param null $data
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\DefaultMockup
     */
    public function saveToMockupCache($objectId, $data = null) {
        if(empty($data)) {
            $data = $this->db->fetchOne("SELECT data FROM " . $this->getStoreTableName() . " WHERE o_id = ? AND tenant = ?", array($objectId, $this->name));
            $data = json_decode($data, true);
        }

        if($this->tenantConfig instanceof IMockupConfig) {
            $mockup = $this->tenantConfig->createMockupObject($objectId, $data['data'], $data['relations']);
        } else {
            throw new InvalidConfigException("Tenant Config is not instance of IMockupConfig");
        }


        $key = $this->createMockupCacheKey($objectId);

        //use cache instance directly to aviod cache locking -> in this case force writing to cache is needed
        //TODO
        $cache = \Pimcore\Cache::getInstance();
        $success = $cache->save(serialize($mockup), \Pimcore\Cache::$cachePrefix . $key, [$this->getMockupCachePrefix()], null);
        $result = \Pimcore\Cache::load($key);
        if($success && $result) {
            $this->db->query("UPDATE " . $this->getStoreTableName() . " SET crc_index = crc_current WHERE o_id = ? and tenant = ?", array($objectId, $this->name));
        } else {
            Logger::err("Element with ID $objectId could not be added to mockup-cache");
        }

        return $mockup;
    }

    /**
     * gets mockup from cache and if not in cache, adds it to cache
     *
     * @param $objectId
     * @return DefaultMockup
     */
    public function getMockupFromCache($objectId) {
        $key = $this->createMockupCacheKey($objectId);
        $cachedItem = \Pimcore\Cache::load($key);

        if(is_string($cachedItem)){
            $cachedItem = unserialize($cachedItem);
        }
        if($cachedItem instanceof DefaultMockup) {
            return $cachedItem;
        }

        Logger::info("Element with ID $objectId was not found in cache, trying to put it there.");
        return $this->saveToMockupCache($objectId);
    }

}