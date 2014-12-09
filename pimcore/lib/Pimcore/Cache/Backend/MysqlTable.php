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

class MysqlTable extends \Zend_Cache_Backend implements \Zend_Cache_Backend_ExtendedInterface {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @param string $id
     * @param bool $doNotTestCacheValidity
     * @return false|null|string
     */
    public function load($id, $doNotTestCacheValidity = false) {
        $data = $this->getDb()->fetchRow("SELECT data,expire FROM cache WHERE id = ?", $id);
        if($data && isset($data["expire"]) && $data["expire"] > time()) {
            return $data["data"];
        }
        return null;
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
     * @param string $tag
     * @return array
     */
    protected function getItemsByTag($tag) {
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

        $lifetime = $this->getLifetime($specificLifetime);

        $this->getDb()->beginTransaction();

        try {
            $this->getDb()->insertOrUpdate("cache", array(
                "data" => $data,
                "id" => $id,
                "expire" => time() + $lifetime,
                "mtime" => time()
            ));

            if (count($tags) > 0) {
                while ($tag = array_shift($tags)) {
                    $this->getDb()->insertOrUpdate("cache_tags", array(
                        "id" => $id,
                        "tag" => $tag
                    ));
                }
            }
            $this->getDb()->commit();
        } catch (\Exception $e) {
            \Logger::error($e);
            $this->getDb()->rollBack();
            $this->truncate();
            return false;
        }

        return true;
    }

    /**
     * @param  string $id
     * @return bool true if OK
     */
    public function remove($id) {

        $this->getDb()->beginTransaction();

        try {
            $this->getDb()->delete("cache", "id = " . $this->getDb()->quote($id));
            $this->getDb()->delete("cache_tags", "id = '".$id."'");

            $this->getDb()->commit();
        } catch (\Exception $e) {
            $this->getDb()->rollBack();
            $this->truncate();
            return false;
        }

        return true;
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

        if ($mode == \Zend_Cache::CLEANING_MODE_ALL) {
            $this->truncate();
        }
        if ($mode == \Zend_Cache::CLEANING_MODE_OLD) {
            $this->getDb()->delete("cache", "expire < unix_timestamp() OR mtime < (unix_timestamp()-864000)");
        }
        if ($mode == \Zend_Cache::CLEANING_MODE_MATCHING_TAG || $mode == \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG) {
            foreach ($tags as $tag) {
                $items = $this->getItemsByTag($tag);
                $quotedIds = array();

                $this->getDb()->beginTransaction();

                try {
                    foreach ($items as $item) {
                        // We call delete directly here because the ID in the cache is already specific for this site
                        $quotedId = $this->getDb()->quote($item);
                        $this->getDb()->delete("cache", "id = " . $quotedId);
                        $quotedIds[] = $quotedId;
                    }

                    if(count($quotedIds) > 0) {
                        $this->getDb()->delete("cache_tags", "id IN (" . implode(",", $quotedIds) . ")");
                    }

                    $this->getDb()->commit();
                } catch (\Exception $e) {
                    $this->getDb()->rollBack();
                    $this->truncate();
                    \Logger::error($e);
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

        return true;
    }
    
    protected function truncate() {
        $this->getDb()->query("TRUNCATE TABLE `cache`");
        $this->getDb()->query("TRUNCATE TABLE `cache_tags`");
        $this->getDb()->query("ALTER TABLE `cache_tags` ENGINE=InnoDB");
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

    public function getMetadatas($id) {

        $data = $this->getDb()->fetchRow("SELECT mtime,expire FROM cache WHERE id = ?", $id);

        if (is_array($data) && isset($data["mtime"])) {
            return array(
                'expire' => $data["expire"],
                'tags' => array(),
                'mtime' => $data["mtime"]
            );
        }
        return false;
    }

    /**
     * @return array
     */
    public function getCapabilities() {
        return array(
            'automatic_cleaning' => false,
            'tags' => true,
            'expired_read' => false,
            'priority' => false,
            'infinite_lifetime' => false,
            'get_list' => false
        );
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        return $this->getDb()->fetchAll("SELECT DISTINCT (id) FROM cache_tags");
    }

    public function test($id)
    {
        $data = $this->getDb()->fetchRow("SELECT mtime,expire FROM cache WHERE id = ?", $id);
        if ($data && isset($data["expire"]) && time() < $data["expire"]) {
            return $data["mtime"];
        }
        return false;
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @throws \Zend_Cache_Exception
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
        return 0;
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        $data = $this->getDb()->fetchRow("SELECT mtime,expire FROM cache WHERE id = ?", $id);
        if ($data && isset($data["expire"]) && time() < $data["expire"]) {
            $lifetime = (int) ($data["expire"] - $data["mtime"]);
            $this->getDb()->update("cache", array("expire" => (time() + $lifetime + (int) $extraLifetime)));
        }
        return true;
    }

    public function getIds()
    {
        return $this->getDb()->fetchAll("SELECT id from cache");
    }

    public function getIdsNotMatchingTags($tags = array())
    {
        return array();
    }
}
