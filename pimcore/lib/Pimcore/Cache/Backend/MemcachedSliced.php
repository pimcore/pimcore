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


class Pimcore_Cache_Backend_MemcachedSliced extends Pimcore_Cache_Backend_Memcached {

    /**
     * @var string
     */
    private $sliceInfoIdentifier = "~slice-info~";

    /**
     * @var bool
     */
    protected $checkedCacheConsistency = false;

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
        $data = parent::load($id, $doNotTestCacheValidity);
        $slicesAmount = $this->getSlicesAmount($data);
        if ($slicesAmount > 0) {
            $data = $this->loadSliced($id, $slicesAmount);
        }

        return $data;
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
     * @param  string $id
     * @return bool true if OK
     */
    public function remove($id) {

        $this->checkCacheConsistency();

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
