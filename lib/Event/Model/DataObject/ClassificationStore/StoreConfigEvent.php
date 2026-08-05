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

use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Contracts\EventDispatcher\Event;

class StoreConfigEvent extends Event
{
    protected StoreConfig $storeConfig;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(StoreConfig $storeConfig)
    {
        $this->storeConfig = $storeConfig;
    }

    public function getStoreConfig(): StoreConfig
    {
        return $this->storeConfig;
    }

    public function setStoreConfig(StoreConfig $storeConfig): void
    {
        $this->storeConfig = $storeConfig;
    }
}
