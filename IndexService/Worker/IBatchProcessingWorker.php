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
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker;

/**
 * Interface for IndexService workers which support patch processing of index data preparation and index updating
 *
 * Interface \OnlineShop\Framework\IndexService\Worker\IBatchProcessingWorker
 */
interface IBatchProcessingWorker extends IWorker {

    /**
     * fills queue based on path
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     */
    public function fillupPreparationQueue(\OnlineShop\Framework\Model\IIndexable $object);


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