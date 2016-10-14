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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Lock;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @property \Pimcore\Model\Tool\Lock $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $key
     * @param int $expire
     * @return bool
     */
    public function isLocked($key, $expire = 120)
    {
        if (!is_numeric($expire)) {
            $expire = 120;
        }

        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);

        // a lock is only valid for a certain time (default: 2 minutes)
        if (!$lock) {
            return false;
        } elseif (is_array($lock) && array_key_exists("id", $lock) && $lock["date"] < (time()-$expire)) {
            if ($expire > 0) {
                Logger::debug("Lock '" . $key . "' expired (expiry time: " . $expire . ", lock date: " . $lock["date"] . " / current time: " . time() . ")");
                $this->release($key);

                return false;
            }
        }

        return true;
    }

    /**
     * @param $key
     * @param int $expire
     * @param int $refreshInterval
     */
    public function acquire($key, $expire = 120, $refreshInterval = 1)
    {
        Logger::debug("Acquiring key: '" . $key . "' expiry: " . $expire);

        if (!is_numeric($refreshInterval)) {
            $refreshInterval = 1;
        }

        while (true) {
            while ($this->isLocked($key, $expire)) {
                sleep($refreshInterval);
            }

            try {
                $this->lock($key, false);

                return true;
            } catch (\Exception $e) {
                Logger::debug($e);
            }
        }
    }

    /**
     * @param $key
     */
    public function release($key)
    {
        Logger::debug("Releasing: '" . $key . "'");

        $this->db->delete("locks", "id = " . $this->db->quote($key));
    }

    /**
     * @param $key
     * @param bool $force
     */
    public function lock($key, $force = true)
    {
        Logger::debug("Locking: '" . $key . "'");

        $updateMethod = $force ? "insertOrUpdate" : "insert";

        $this->db->$updateMethod("locks", [
            "id" => $key,
            "date" => time()
        ]);
    }

    public function getById($key)
    {
        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);
        $this->assignVariablesToModel($lock);
    }
}
