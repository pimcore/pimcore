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


/**
 * Interface for IndexService workers which support patch processing of index data preparation and index updating
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker
 */
interface OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker extends OnlineShop_Framework_IndexService_Tenant_IWorker {

    /**
     * fills queue based on path
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public function fillupPreparationQueue(OnlineShop_Framework_ProductInterfaces_IIndexable $object);


    /**
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200);



    /**
     * processes the update index queue - updates all elements where current_crc != index_crc
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return $int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200);

}