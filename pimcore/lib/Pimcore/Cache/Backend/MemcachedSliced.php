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


class Pimcore_Cache_Backend_MemcachedSliced extends Zend_Cache_Backend_Memcached {

    private $db;
    private $sliceInfoIdentifier = "~slice-info~";


    /**
     * @return Zend_Db_Adapter_Abstract
     */
    private function getDb() {
        if (!$this->db) {
            $this->db = Pimcore_Resource::get();
        }
        return $this->db;
    }


    private function removeTag($tag) {
        $this->getDb()->delete("cache_tags", "tag = '" . $tag . "'");
    }

    private function saveTags($id, $tags) {

        foreach ($tags as $tag) {
            try {
                $this->getDb()->insert("cache_tags", array(
                      "id" => $id,
                      "tag" => $tag
                 ));
            }
            catch (Exception $e) {
                // already exists
            }
        }
    }

    private function clearTags() {
        $this->getDb()->delete("cache_tags");
    }

    private function getItemsByTag($tag) {
        $itemIds = $this->getDb()->fetchAll("SELECT id FROM cache_tags WHERE tag = '" . $tag . "'");
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

        //hack to slice data if > 1M
        if (strlen($data) > 1024 * (1024 - 1)) {
            $result = $this->saveSliced($id, $data, $specificLifetime);
        } else {
            $result = $this->saveNotSliced($id, $data, $specificLifetime);
        }
        if ($result) {
            if (count($tags) > 0) {
                $this->saveTags($id, $tags);
            }
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

            $itemIds = $this->getDb()->fetchAll("SELECT id FROM cache_tags WHERE " . implode(" AND ", $condParts));

            $items = array();
            foreach ($itemIds as $item) {
                $items[] = $item["id"];
            }

            foreach ($items as $item) {
                $this->remove($item);
            }
        }
    }


    /**
     * @param  string $id
     * @return array tags for given id
     */
    protected function getTagsById($id) {
        $itemIds = $this->getDb()->fetchAll("SELECT tag FROM cache_tags WHERE id = '" . $id . "'");
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

        $this->getDb()->delete("cache_tags", "id = '" . $id . "'");

        //check if this is a sliced one
        $data = parent::load($id);
        $slicesAmount = $this->getSlicesAmount($data);
        if ($slicesAmount > 0) {
            $result = $this->removeSliced($id, $slicesAmount);
        } else {
            $result = parent::remove($id);

            // security check if the deletion fails
            if (!$result && $this->_memcache->get($id) !== false) {
                $result = $this->_memcache->flush();
            }
        }
        return $result;
    }


    public function getCapabilities() {
        $capabilities = parent::getCapabilities();
        $capabilities["tags"] = true;

        return $capabilities;
    }

    public function load($id, $doNotTestCacheValidity = false) {
        $data = parent::load($id, $doNotTestCacheValidity);
        $slicesAmount = $this->getSlicesAmount($data);
        if ($slicesAmount > 0) {
            $data = $this->loadSliced($id, $slicesAmount);
        }

        return $data;
    }

    private function getSlicesAmount($data) {
        if (substr($data, 0, strlen($this->sliceInfoIdentifier)) == $this->sliceInfoIdentifier) {
            $amountSlices = (int)str_replace($this->sliceInfoIdentifier, "", $data);
        } else {
            $amountSlices = 0;
        }

        return $amountSlices;
    }

    private function loadSliced($id, $slicesAmount) {
        //load all slices
        $slicedData = "";
        for ($i = 0; $i < $slicesAmount; $i++) {
            $sliceKey = $id . "_slice_" . $i;
            $slice = parent::load($sliceKey);
            if (!$slice) {
                Logger::debug("Error getting slice #" . $i . " for key " . $id . " (" . $sliceKey . ")");
                return false;
            } else {
                Logger::debug("Successfully got slice #" . $i . " for key " . $id . " (" . $sliceKey . ")");
                $slicedData .= $slice;
            }
        }
        Logger::debug("Successfully got sliced data for key " . $id);

        return $slicedData;
    }

    private function removeSliced($id, $slicesAmount, $skipCheck = false) {
        $result = false;
        for ($i = 0; $i < $slicesAmount; $i++) {
            $sliceKey = $id . "_slice_" . $i;

            $result = parent::remove($sliceKey);

            // security check if the deletion fails
            if (!$result && !$skipCheck && $this->_memcache->get($sliceKey) !== false) {
                Logger::warn("Error deleting slice #" . $i . " for key " . $id . " (" . $sliceKey . ") - flushing memcache!");
                $result = $this->_memcache->flush();
            }
        }

        return $result;
    }

    private function saveSliced($id, $data, $specificLifetime) {
        $slices = str_split($data, 1024 * (1024 - 1));
        $sliceInfo = $this->sliceInfoIdentifier . count($slices);
        Logger::debug("Data for key " . $id . " > 1M - will be sliced to " . count($slices) . " slices");

        $success = false;
        foreach ($slices as $idx => $slice) {
            $sliceKey = $id . "_slice_" . $idx;
            $success = $this->saveNotSliced($sliceKey, $slice, $specificLifetime);

            if (!$success) {
                Logger::debug("slice #" . $idx . " for key " . $id . " (" . $sliceKey . ") NOT written. cancelling.");
                $this->removeSliced($id, $idx - 1, true);
                break;
            } else {
                Logger::debug("slice #" . $idx . " for key " . $id . " (" . $sliceKey . ") written");
            }
        }

        if ($success) {
            $success = $this->saveNotSliced($id, $sliceInfo, $specificLifetime);
        }

        return $success;
    }

    private function saveNotSliced($id, $data, $specificLifetime) {
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
        if ($result == false) {
            $result = $this->_memcache->set($id, array($data, time()), $flag, $lifetime);
        }

        return $result;
    }

}
