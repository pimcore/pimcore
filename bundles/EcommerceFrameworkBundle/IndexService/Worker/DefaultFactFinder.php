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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFactFinder as DefaultFactFinderConfig;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\FactFinderConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultRelations;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Tool\Text;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated since version 6.7.0 and will be removed in 7.0.0.
 *
 * @property DefaultFactFinderConfig $tenantConfig
 */
class DefaultFactFinder extends AbstractMockupCacheWorker implements WorkerInterface, BatchProcessingWorkerInterface
{
    const STORE_TABLE_NAME = 'ecommerceframework_productindex_store_factfinder';
    const MOCKUP_CACHE_PREFIX = 'ecommerce_mockup_factfinder';

    /**
     * @var array
     */
    protected $_sqlChangeLog = [];

    /**
     * @param FactFinderConfigInterface $tenantConfig
     * @param ConnectionInterface $db
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $workerMode
     */
    public function __construct(FactFinderConfigInterface $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher, string $workerMode = null)
    {
        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.7.0 and will be removed in 7.0.0.',
            E_USER_DEPRECATED
        );

        parent::__construct($tenantConfig, $db, $eventDispatcher, $workerMode);
    }

    protected function getSystemAttributes()
    {
        return ['o_id',
            'o_virtualProductId',
            'o_virtualProductActive',
            'o_classId',
            'o_parentId',
            'o_type',
            'active',
            'tenant',
            'categoryPaths',
            'categoryIds',
            'parentCategoryIds',
            'inProductList',
            'crc_current',
            'crc_index',
            'priceSystemName',
            'worker_timestamp',
            'worker_id',
            'in_preparation_queue',
            'preparation_worker_timestamp',
            'preparation_worker_id', ];
    }

    protected function dbexec($sql)
    {
        $this->db->query($sql);
        $this->logSql($sql);
    }

    protected function logSql($sql)
    {
        Logger::info($sql);

        $this->_sqlChangeLog[] = $sql;
    }

    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures()
    {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        /**
         * @TODO Pimcore 7 - remove worker columns
         */
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $this->getStoreTableName() . "` (
          `o_id` $primaryIdColumnType,
          `o_virtualProductId` $idColumnType,
          `o_virtualProductActive` TINYINT(1) NOT NULL,
          `o_classId` int(11) NOT NULL,
          `o_parentId`  bigint(20) NOT NULL DEFAULT '0',
          `o_type` varchar(20) NOT NULL,
          `active` TINYINT(1) NOT NULL,
          `inProductList` TINYINT(1) NOT NULL,
          `tenant` varchar(50) NOT NULL DEFAULT '',
          `categoryPaths` varchar(500) NOT NULL DEFAULT '',
          `crc_current` bigint(11) DEFAULT NULL,
          `crc_index` bigint(11) DEFAULT NULL,
          `categoryIds` varchar(255) NOT NULL,
          `parentCategoryIds` varchar(255) NOT NULL,
          `worker_timestamp` int(11) DEFAULT NULL,
          `worker_id` varchar(20) DEFAULT NULL,
          `in_preparation_queue` tinyint(1) DEFAULT NULL,
          `preparation_worker_timestamp` int(11) DEFAULT NULL,
          `preparation_worker_id` varchar(20) DEFAULT NULL,
          `priceSystemName` varchar(50) NOT NULL,
          `update_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `update_error` VARCHAR(255) NULL DEFAULT NULL,
          PRIMARY KEY (`o_id`,`tenant`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $data = $this->db->fetchAll('SHOW COLUMNS FROM ' . $this->getStoreTableName());
        $columns = [];
        foreach ($data as $d) {
            if (!in_array($d['Field'], $this->getSystemAttributes())) {
                $columns[$d['Field']] = $d['Field'];
            }
        }

        $systemColumns = $this->getSystemAttributes();

        $columnsToDelete = $columns;
        $columnsToAdd = [];

        foreach ($this->tenantConfig->getAttributes() as $attribute) {
            if (!array_key_exists($attribute->getName(), $columns)) {
                $doAdd = true;
                if (null !== $attribute->getInterpreter()) {
                    if ($attribute->getInterpreter() instanceof DefaultRelations) {
                        $doAdd = false;
                    }
                }

                if ($doAdd) {
                    $columnsToAdd[$attribute->getName()] = $attribute->getType();
                }
            }

            unset($columnsToDelete[$attribute->getName()]);
        }

        foreach ($columnsToDelete as $c) {
            if (!in_array($c, $systemColumns)) {
                $this->dbexec('ALTER TABLE `' . $this->getStoreTableName() . '` DROP COLUMN `' . $c . '`;');
            }
        }

        foreach ($columnsToAdd as $c => $type) {
            $this->dbexec('ALTER TABLE `' . $this->getStoreTableName() . '` ADD `' . $c . '` ' . $type . ';');
        }
    }

    /**
     * deletes given element from index
     *
     * @param IndexableInterface $object
     *
     * @return void
     */
    public function deleteFromIndex(IndexableInterface $object)
    {
        // TODO: Implement deleteFromIndex() method.
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IndexableInterface $object
     *
     * @return array returns the processed sub-objects that can be used for the index update.
     */
    public function prepareDataForIndex(IndexableInterface $object): array
    {
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach ($subObjectIds as $subObjectId => $object) {
            /**
             * @var IndexableInterface $object
             */
            if ($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = AbstractObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = AbstractObject::doHideUnpublished();
                AbstractObject::setHideUnpublished(false);

                $data = $this->getDefaultDataForIndex($object, $subObjectId);
                $data['categoryPaths'] = implode('|', (array)$data['categoryPaths']);
                $data['crc_current'] = '';
                $data['in_preparation_queue'] = 0;

                foreach ($this->tenantConfig->getAttributes() as $attribute) {
                    try {
                        $value = $attribute->getValue($object, $subObjectId, $this->tenantConfig);
                        $value = $attribute->interpretValue($value);

                        if (is_array($value)) {
                            $value = array_filter($value);
                            $value = '|' . implode('|', $value) . '|';
                        }

                        $data[$attribute->getName()] = $value;
                    } catch (\Exception $e) {
                        Logger::err('Exception in IndexService: ' . $e);
                    }
                }

                if ($a) {
                    \Pimcore::setAdminMode();
                }
                AbstractObject::setGetInheritedValues($b);
                AbstractObject::setHideUnpublished($hidePublishedMemory);

                foreach ($data as $key => $value) {
                    $data[$key] = Text::removeLineBreaks($value);
                }
                $data['crc_current'] = crc32(serialize($data));
                $this->insertDataToIndex($data, $subObjectId);
            } else {
                Logger::info("Don't adding product " . $subObjectId . ' to index ' . $this->name . '.');
                $this->doDeleteFromIndex($subObjectId, $object);
            }
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

        return $subObjectIds;
    }

    /**
     * updates given element in index
     *
     * @param IndexableInterface $object
     *
     * @return void
     */
    public function updateIndex(IndexableInterface $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $this->prepareDataForIndex($object);
        $this->fillupPreparationQueue($object);
    }

    /**
     * @deprecated
     *
     * first run processUpdateIndexQueue of trait and then commit updated entries if there are some
     *
     * @param int $limit
     *
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        $entriesUpdated = parent::processUpdateIndexQueue($limit);
        if ($entriesUpdated) {
            // TODO csv schreiben?
//            $this->commitUpdateIndex();
        }

        return $entriesUpdated;
    }

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *d
     *
     * @return mixed
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFactFinder($this->getTenantConfig());
    }

    /**
     * only prepare data for updating index
     *
     * @param int $objectId
     * @param array|null $data
     * @param array|null $metadata
     */
    protected function doUpdateIndex($objectId, $data = null, $metadata = null)
    {
    }

    /**
     * @param int $subObjectId
     * @param IndexableInterface|null $object
     */
    protected function doDeleteFromIndex($subObjectId, IndexableInterface $object = null)
    {
    }

    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix()
    {
        return self::MOCKUP_CACHE_PREFIX;
    }
}
