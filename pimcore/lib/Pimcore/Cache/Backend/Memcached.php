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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


class Pimcore_Cache_Backend_Memcached extends Zend_Cache_Backend_Memcached {

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * @var bool
     */
    private $checkedCacheConsistency = false;

    /**
     * @return void
     */
    private function checkCacheConsistency() {
        // if the cache_tags table is empty, flush the cache
        // reason: the cache tags are stored in a MEMORY table, that means that a restart of the mysql server causes the loss
        // of all data in this table but the cache still exists, so there is an inconsistency because then it's possible that
        // there are outdated or just wrong items in the cache
        if(!$this->checkedCacheConsistency) {
            $this->checkedCacheConsistency = true;
            
            $res = $this->getDb()->fetchOne("SELECT id FROM cache_tags LIMIT 1");
            if(!$res) {
                $this->clean(Zend_Cache::CLEANING_MODE_ALL);
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
     * @return Zend_Db_Adapter_Abstract
     */
    private function getDb () {
        if(!$this->db) {
            $this->db = Pimcore_Resource::get();
        }
        return $this->db;
    }
    
    /**
     * @param string $tag
     * @return void
     */
    private function removeTag($tag) {
        $this->getDb()->delete("cache_tags", "tag = '".$tag."'");
    }

    /**
     * @param string $id
     * @param array $tags
     * @return void
     */
    private function saveTags($id, $tags) {

        while ($tag = array_shift($tags)) {
            try {
                $this->getDb()->insert("cache_tags", array(
                    "id" => $id, 
                    "tag" => $tag
                ));
            }
            catch (Exception $e) {
                if(strpos(strtolower($e->getMessage()), "is full") !== false) {
                    // it seems that the MEMORY table is on the limit an full
                    // change the storage engine of the cache tags table to MyISAM
                    $this->getDb()->query("ALTER TABLE `cache_tags` ENGINE=InnoDB");

                    // try it again
                    $tags[] = $tag;
                } else {
                    // it seems that the item does already exist
                    continue;
                }
            }
        }
    }

    /**
     * @return void
     */
    private function clearTags () {
        $this->getDb()->delete("cache_tags");
    }

    /**
     * @param string $tag
     * @return array
     */
    private function getItemsByTag($tag) {

        $this->checkCacheConsistency();

        $itemIds = $this->getDb()->fetchAll("SELECT id FROM cache_tags WHERE tag = ?", $tag);
        $items = array();
        
        foreach ($itemIds as $item) {
            $items[] = $item["id"];
        }
        
        
        return $items;
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

        $lifetime = $this->getLifetime($specificLifetime);
        if ($this->_options['compression']) {
            $flag = MEMCACHE_COMPRESSED;
        } else {
            $flag = 0;
        }
        
        // hack may it works also without it
        $this->_memcache->delete($id);
        // hack end
        
        $result = $this->_memcache->replace($id, array($data, time()), $flag, $lifetime);
        if( $result == false ) { 
            $result = $this->_memcache->set($id, array($data, time()), $flag, $lifetime);
        } 
        
        
        if (count($tags) > 0) {
            $this->saveTags($id, $tags);
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
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {

        $this->checkCacheConsistency();

        if ($mode == Zend_Cache::CLEANING_MODE_ALL) {
            $this->clearTags();
            return $this->_memcache->flush();
        }
        if ($mode == Zend_Cache::CLEANING_MODE_OLD) {
            Logger::warning("Zend_Cache_Backend_Memcached::clean() : CLEANING_MODE_OLD is unsupported by the Memcached backend");
        }
        if ($mode == Zend_Cache::CLEANING_MODE_MATCHING_TAG || $mode == Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG) {
            foreach ($tags as $tag) {
                $items = $this->getItemsByTag($tag);
                foreach ($items as $item) {
                    // We call delete directly here because the ID in the cache is already specific for this site
                    $this->remove($item);
                }
            }            
        }
        if ($mode == Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG) {
            
            $condParts = array("1=1");
            foreach ($tags as $tag) {
                $condParts[] = "tag != '" . $tag . "'";
            }
            
            $itemIds = $this->getDb()->fetchAll("SELECT id FROM cache_tags WHERE ".implode(" AND ",$condParts));
            
            $items = array();
            foreach ($itemIds as $item) {
                $items[] = $item["id"];
            }
            
            foreach ($items as $item) {
                $this->remove($item);
            }
        }

        // insert dummy for the consistency check
        try {
            $this->getDb()->insert("cache_tags", array(
                "id" => "___consistency_check___",
                "tag" => "___consistency_check___"
            ));
        } catch (Exception $e) {
            // doesn't matter as long as the item exists
        }

        return true;
    }
    
    
    /**
     * @param  string $id
     * @return array tags for given id
     */
    protected function getTagsById($id) {
        $itemIds = $this->getDb()->fetchAll("SELECT tag FROM cache_tags WHERE id = ?", $id);
        $items = array();

        foreach ($itemIds as $item) {
            $items[] = $item["tag"];
        }
        return $items;
    }

    /**
     * @param  string $id
     * @return bool true if OK
     */
    public function remove($id) {

        $this->checkCacheConsistency();

        $this->getDb()->delete("cache_tags", "id = '".$id."'");

        $result = parent::remove($id);

        // security check if the deletion fails 
        if(!$result && $this->_memcache->get($id) !== false) {
            $this->_memcache->flush();
        }

        return $result;
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
