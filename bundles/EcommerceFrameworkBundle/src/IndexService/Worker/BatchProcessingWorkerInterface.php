<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
    public function fillupPreparationQueue(IndexableInterface $object): void;

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IndexableInterface $object
     *
     * @return array returns the processed subobjects that can be used for the index update.
     */
    public function prepareDataForIndex(IndexableInterface $object): array;

    /**
     * resets the store table by marking all items as "in preparation", so items in store will be regenerated
     *
     * @return void
     */
    public function resetPreparationQueue(): void;

    /**
     * resets the store table to initiate a re-indexing
     *
     * @return void
     */
    public function resetIndexingQueue(): void;
}
