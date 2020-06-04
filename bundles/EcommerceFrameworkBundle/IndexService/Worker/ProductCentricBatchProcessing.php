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


abstract class ProductCentricBatchProcessingWorker extends AbstractBatchProcessingWorker
{

    public function getBatchProcessingStoreTableName(): string
    {
        return $this->getStoreTableName();
    }

    public function updateItemInIndex($objectId): void
    {
        return $this->doUpdateIndex($objectId);
    }

    public function commitBatchToIndex(): void
    {
        //nothing to do by default
    }

}