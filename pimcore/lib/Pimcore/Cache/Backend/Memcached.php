<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Cache\Backend;

use Pimcore\Resource;

class Memcached extends \Zend_Cache_Backend_Memcached {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var bool
     */
    protected $checkedCacheConsistency = false;

    /**
     * @param array $options
     */
    public function __construct(array $options = array()) {

        $this->_options["tags_do_not_switch_to_innodb"] = null;

        parent::__construct($options);
    }

    /**
     * @return void
     */
    protected function checkCacheConsistency() {
        // if the cache_tags table is empty, flush the cache
        // reason: the cache tags are stored in a MEMORY table, that means that a restart of the mysql server causes the loss
        // of all data in this table but the cache still exists, so there is an inconsistency because then it's possible that
        // there are outdated or just wrong items in the cache
        if(!$this->checkedCacheConsistency) {
            $this->checkedCacheConsistency = true;

            $res = $this->getDb()->fetchOne("SELECT id FROM cache_tags LIMIT 1");
            if(!$res) {
                $this->clean(\Zend_Cache::CLEANING_MODE_ALL);
            }
        }
    }

    /**
     * @param $id
     * @param bool $doNotTestCacheValidity
     * @return void
     */
    public function load($id, $doNotTestCacheValidity = false) {

        if(!$doNotTestCacheValidity) {
            $this->checkCacheConsistency();
        }

        return parent::load($id, $doNotTestCacheValidity);
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    protected function getDb () {
        if(!$this->db) {
            // we're using a new mysql connection here to avoid problems with active (nested) transactions
            \Logger::debug("Initialize dedicated MySQL connection for the cache adapter");
            $this->db = Resource::getConnection();
        }
        return $this->db;
    }

    /**
     * @param string $id
     * @param array $tags
     * @return void
     */
    protected function saveTags($id, $tags) {

        //$this->getDb()->beginTransaction();

        try {
            while ($tag = array_shift($tags)) {
                try {
                    $this->getDb()->insertOrUpdate("cache_tags", array(
                        "id" => $id,
                        "tag" => $tag
                    ));
                }
                catch (\Exception $e) {
                    if(strpos(strtolower($e->getMessage()), "is full") !== false) {

                        \Logger::warning($e);

                        if($this->_options["tags_do_not_switch_to_innodb"]) {
                            $this->clean();
                        } else {
                            // it seems that the MEMORY table is on the limit an full
                            // change the storage engine of the cache tags table to InnoDB
                            $this->getDb()->query("ALTER TABLE `cache_tags` ENGINE=InnoDB");
                        }

                        // try it again
                        $tags[] = $tag;
                    } else {
                        // it seems that the item does already exist
                        throw $e;
                    }
                }
            }
            //$this->getDb()->commit();

        } catch (\Exception $e) {
            \Logger::error($e);
        }
    }

    /**
     * @return void
     */
    protected function clearTags () {
        $this->getDb()->query("TRUNCATE TABLE `cache_tags`");
        $this->getDb()->query("ALTER TABLE `cache_tags` ENGINE=MEMORY");
    }

    /**
     * @param string $tag
     * @return array
     */
    protected function getItemsByTag($tag) {

        $this->checkCacheConsistency();
        $itemIds = $this->getDb()->fetchCol("SELECT id FROM cache_tags WHERE tag = ?", $tag);
        return $itemIds;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false) {

        $this->checkCacheConsistency();

        $result = parent::save($data, $id, array(), $specificLifetime);

        if($result) {
            if (count($tags) > 0) {
                $this->saveTags($id, $tags);
            }
        } else {
            $this->remove($id);
        }

        return $result;
    }

    /**
     * @param  string $id
     * @return bool true if OK
     */
    public function remove($id) {

        $this->checkCacheConsistency();

        $result = parent::remove($id);

        // using func_get_arg() to be compatible with the interface
        // when the 2ng argument is true, do not clean the cache tags
        if($result && func_num_args() > 1 && func_get_arg(1) !== true) {
            $this->getDb()->delete("cache_tags", "id = '".$id."'");
        }

        // security check if the deletion fails
        if(!$result && $this->_memcache->get($id) !== false) {
            $this->_memcache->flush();
        }

        return $result;
    }

    /** 
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean True if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {

        $this->checkCacheConsistency();

        if ($mode == \Zend_Cache::CLEANING_MODE_ALL) {
            $this->clearTags();
            return $this->_memcache->flush();
        }
        if ($mode == \Zend_Cache::CLEANING_MODE_OLD) {
            \Logger::debug("Zend_Cache_Backend_Memcached::clean() : CLEANING_MODE_OLD is unsupported by the Memcached backend");
        }
        if ($mode == \Zend_Cache::CLEANING_MODE_MATCHING_TAG || $mode == \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG) {
            foreach ($tags as $tag) {
                $items = $this->getItemsByTag($tag);
                $quotedIds = array();

                foreach ($items as $item) {
                    // We call delete directly here because the ID in the cache is already specific for this site
                    if($this->remove($item, true)) {
                        $quotedIds[] = $this->getDb()->quote($item);
                    }
                }

                if(count($quotedIds) > 0) {
                    $this->getDb()->delete("cache_tags", "id IN (" . implode(",", $quotedIds) . ")");
                }
            }
        }
        if ($mode == \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG) {
            
            $condParts = array("1=1");
            foreach ($tags as $tag) {
                $condParts[] = "tag != '" . $tag . "'";
            }
            
            $itemIds = $this->getDb()->fetchCol("SELECT id FROM cache_tags WHERE ".implode(" AND ",$condParts));

            foreach ($itemIds as $item) {
                $this->remove($item);
            }
        }

        // insert dummy for the consistency check
        try {
            $this->getDb()->insertOrUpdate("cache_tags", array(
                "id" => "___consistency_check___",
                "tag" => "___consistency_check___"
            ));
        } catch (\Exception $e) {
            // doesn't matter as long as the item exists
        }

        return true;
    }
    
    
    /**
     * @param  string $id
     * @return array tags for given id
     */
    protected function getTagsById($id) {
        $itemIds = $this->getDb()->fetchCol("SELECT tag FROM cache_tags WHERE id = ?", $id);
        return $itemIds;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getIdsMatchingAnyTags($tags = array()) {
        $tags_ = array();
        foreach($tags as $tag) {
            $tags_[] = $this->getDb()->quote($tag);
        }

        $itemIds = $this->getDb()->fetchCol("SELECT id FROM cache_tags WHERE tag IN (".implode(",",$tags_).")");
        return $itemIds;
    }


    /**
     * @param array $tags
     * @return array
     */
    public function getIdsMatchingTags($tags = array()) {

        $tags_ = array();
        foreach($tags as $tag) {
            $tags_[] = " tag = ".$this->getDb()->quote($tag);
        }

        $itemIds = $this->getDb()->fetchCol("SELECT id FROM cache_tags WHERE ".implode(" AND ",$tags_));
        return $itemIds;
    }


    /**
     * @return array
     */
    public function getCapabilities() {
        $capabilities = parent::getCapabilities();
        $capabilities["tags"] = true;

        return $capabilities;
    }
}
