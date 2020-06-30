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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class ProductCentricBatchProcessingWorker extends AbstractBatchProcessingWorker
{
    /**
     * @deprecated
     * @TODO Pimcore 7 - remove this
     */
    const WORKER_MODE_LEGACY = 'legacy';
    const WORKER_MODE_PRODUCT_CENTRIC = 'product_centric';

    /**
     * @deprecated Will be removed in Pimcore 7
     *
     * @var string
     */
    protected $workerMode;

    /**
     * @param ConfigInterface $tenantConfig
     * @param ConnectionInterface $db
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $workerMode
     */
    public function __construct(ConfigInterface $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher, string $workerMode = null)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher);
        $this->workerMode = $workerMode;

        if ($workerMode == self::WORKER_MODE_LEGACY) {
            @trigger_error(
                'Worker_mode "LEGACY" is deprecated since version 6.7.0 and will be removed in 7.0.0. Default will be "PRODUCT_CENTRIC"',
                E_USER_DEPRECATED
            );
        }
    }

    public function getBatchProcessingStoreTableName(): string
    {
        return $this->getStoreTableName();
    }

    public function updateItemInIndex($objectId): void
    {
        $this->doUpdateIndex($objectId);
    }

    public function commitBatchToIndex(): void
    {
        //nothing to do by default
    }

    /**
     * creates store table
     */
    protected function createOrUpdateStoreTable()
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
            $idColumnType = $this->tenantConfig->getIdColumnType(false);

            $this->db->query('CREATE TABLE IF NOT EXISTS `' . $this->getBatchProcessingStoreTableName() . "` (
              `o_id` $primaryIdColumnType,
              `o_virtualProductId` $idColumnType,
              `tenant` varchar(50) NOT NULL DEFAULT '',
              `data` longtext CHARACTER SET latin1,
              `crc_current` bigint(11) DEFAULT NULL,
              `crc_index` bigint(11) DEFAULT NULL,
              `in_preparation_queue` tinyint(1) DEFAULT NULL,
              `update_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
              `update_error` CHAR(255) NULL DEFAULT NULL,
              `preparation_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
              `preparation_error` VARCHAR(255) NULL DEFAULT NULL,
              `trigger_info` VARCHAR(255) NULL DEFAULT NULL,
              `metadata` text,
              PRIMARY KEY (`o_id`,`tenant`),
              KEY `update_worker_index` (`tenant`,`crc_current`,`crc_index`),
              KEY `preparation_status_index` (`tenant`,`preparation_status`),
              KEY `in_preparation_queue_index` (`tenant`,`in_preparation_queue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        } else {
            //@TODO Pimcore 7 - remove this
            parent::createOrUpdateStoreTable();
        }
    }

    /**
     * Inserts the data do the store table
     *
     * @param array $data
     * @param int $subObjectId
     */
    protected function insertDataToIndex($data, $subObjectId)
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            $currentEntry = $this->db->fetchRow('SELECT crc_current, in_preparation_queue FROM ' . $this->getStoreTableName() . ' WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
            if (!$currentEntry) {
                $this->db->insert($this->getStoreTableName(), $data);
            } elseif ($currentEntry['crc_current'] != $data['crc_current']) {
                $this->executeTransactionalQuery(function () use ($data, $subObjectId) {
                    $this->db->updateWhere($this->getStoreTableName(), $data, 'o_id = ' . $this->db->quote((string)$subObjectId) . ' AND tenant = ' . $this->db->quote($this->name));
                });
            } elseif ($currentEntry['in_preparation_queue']) {

                //since no data has changed, just update flags, not data
                $this->executeTransactionalQuery(function () use ($subObjectId) {
                    $this->db->query('UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 0 WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
                });
            }
        } else {
            // @TODO Pimcore 7 - remove this
            parent::insertDataToIndex($data, $subObjectId);
        }
    }

    /**
     * @inheritDoc
     * @TODO Pimcore 7 - remove this
     */
    public function processPreparationQueue($limit = 200)
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            throw new \Exception('Not supported anymore in ' . self::WORKER_MODE_PRODUCT_CENTRIC . ' mode. Use ecommerce:indexservice:process-preparation-queue command instead.');
        }

        return parent::processPreparationQueue($limit);
    }

    /**
     * @inheritDoc
     * @TODO Pimcore 7 - remove this
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            throw new \Exception('Not supported anymore in ' . self::WORKER_MODE_PRODUCT_CENTRIC . ' mode. Use ecommerce:indexservice:process-update-queue command instead.');
        }

        return parent::processUpdateIndexQueue($limit);
    }

    /**
     * resets the store table by marking all items as "in preparation", so items in store will be regenerated
     */
    public function resetPreparationQueue()
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            Logger::info('Index-Actions - Resetting preparation queue');
            $className = (new \ReflectionClass($this))->getShortName();
            $query = 'UPDATE '. $this->getStoreTableName() ." SET
                        preparation_status = '',
                        preparation_error = '',
                        trigger_info = ?,
                        in_preparation_queue = 1 WHERE tenant = ?";
            $this->db->query($query, [
                sprintf('Reset preparation queue in "%s".', $className),
                $this->name,
            ]);
        } else {
            // @TODO Pimcore 7 - remove this
            parent::resetPreparationQueue();
        }
    }

    /**
     * resets the store table to initiate a re-indexing
     */
    public function resetIndexingQueue()
    {
        if ($this->workerMode == self::WORKER_MODE_PRODUCT_CENTRIC) {
            Logger::info('Index-Actions - Resetting index queue');
            $className = (new \ReflectionClass($this))->getShortName();
            $query = 'UPDATE '. $this->getStoreTableName() .' SET 
                        trigger_info = ?,
                        crc_index = 0 WHERE tenant = ?';
            $this->db->query($query, [
                sprintf('Reset indexing queue in "%s".', $className),
                $this->name,
            ]);
        } else {
            // @TODO Pimcore 7 - remove this
            parent::resetIndexingQueue();
        }
    }
}
