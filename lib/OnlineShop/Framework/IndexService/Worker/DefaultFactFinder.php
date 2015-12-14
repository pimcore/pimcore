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

namespace OnlineShop\Framework\IndexService\Worker;

class DefaultFactFinder extends AbstractWorker implements IWorker, IBatchProcessingWorker
{
    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing
    {
        \OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing::processUpdateIndexQueue as traitProcessUpdateIndexQueue;
    }

    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\MockupCache;


    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store_factfinder";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup_factfinder";


    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures()
    {
        $this->createOrUpdateStoreTable();
    }

    /**
     * deletes given element from index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return void
     */
    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object)
    {
        // TODO: Implement deleteFromIndex() method.
    }



    /**
     * updates given element in index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return void
     */
    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object)
    {
        if(!$this->tenantConfig->isActive($object))
        {
            \Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);
        $this->fillupPreparationQueue($object);
    }


    /**
     * first run processUpdateIndexQueue of trait and then commit updated entries if there are some
     *
     * @param int $limit
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        $entriesUpdated = $this->traitProcessUpdateIndexQueue($limit);
        if($entriesUpdated)
        {
            // TODO csv schreiben?
//            $this->commitUpdateIndex();
        }

        return $entriesUpdated;
    }



    /**
     * returns product list implementation valid and configured for this worker/tenant
     *d
     * @return mixed
     */
    public function getProductList()
    {
        return new \OnlineShop\Framework\IndexService\ProductList\DefaultFactFinder( $this->getTenantConfig() );
    }


    /**
     * only prepare data for updating index
     *
     * @param $objectId
     * @param null $data
     */
    protected function doUpdateIndex($objectId, $data = null)
    {

    }


    /**
     * @param int $objectId
     * @todo
     */
    protected function doDeleteFromIndex($objectId, \OnlineShop\Framework\Model\IIndexable $object = null)
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

