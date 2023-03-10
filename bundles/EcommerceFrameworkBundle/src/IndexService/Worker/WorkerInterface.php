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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

/**
 * Interface for IndexService workers
 */
interface WorkerInterface
{
    const MULTISELECT_DELIMITER = '#;#';

    /**
     * returns all attributes marked as general search attributes for full text search
     *
     * @return array
     */
    public function getGeneralSearchAttributes(): array;

    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures(): void;

    /**
     * deletes given element from index
     *
     * @param IndexableInterface $object
     *
     * @return void
     */
    public function deleteFromIndex(IndexableInterface $object): void;

    /**
     * updates given element in index
     *
     * @param IndexableInterface $object
     *
     * @return void
     */
    public function updateIndex(IndexableInterface $object): void;

    /**
     * returns all index attributes
     *
     * @param bool $considerHideInFieldList
     *
     * @return array
     */
    public function getIndexAttributes(bool $considerHideInFieldList = false): array;

    /**
     * returns all filter groups
     *
     * @return array
     */
    public function getAllFilterGroups(): array;

    /**
     * retruns all index attributes for a given filter group
     *
     * @param string $filterGroup
     *
     * @return array
     */
    public function getIndexAttributesByFilterGroup(string $filterGroup): array;

    /**
     * returns current tenant configuration
     *
     * @return ConfigInterface
     */
    public function getTenantConfig(): ConfigInterface;

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return ProductListInterface
     */
    public function getProductList(): ProductListInterface;
}
