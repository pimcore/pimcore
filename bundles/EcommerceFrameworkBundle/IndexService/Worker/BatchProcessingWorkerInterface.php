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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

/**
 * Interface for IndexService workers which support batch processing of index data preparation and index updating
 */
interface BatchProcessingWorkerInterface extends WorkerInterface
{
    /**
     * fills queue based on path
     *
     * @param IndexableInterface $object
     */
    public function fillupPreparationQueue(IndexableInterface $object);

    /**
     * @deprecated will be removed in Pimcore 7.0
     * @TODO Pimcore 7 - remove this
     *
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200);

    /**
     * @deprecated will be removed in Pimcore 7.0
     * @TODO Pimcore 7 - remove this
     *
     * processes the update index queue - updates all elements where current_crc != index_crc
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200);
}

class_alias(BatchProcessingWorkerInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IBatchProcessingWorker');
