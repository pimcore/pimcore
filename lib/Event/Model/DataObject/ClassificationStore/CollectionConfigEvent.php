<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event\Model\DataObject\ClassificationStore;

use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Symfony\Contracts\EventDispatcher\Event;

class CollectionConfigEvent extends Event
{
    protected CollectionConfig $collectionConfig;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(CollectionConfig $collectionConfig)
    {
        $this->collectionConfig = $collectionConfig;
    }

    public function getCollectionConfig(): CollectionConfig
    {
        return $this->collectionConfig;
    }

    public function setCollectionConfig(CollectionConfig $collectionConfig): void
    {
        $this->collectionConfig = $collectionConfig;
    }
}
