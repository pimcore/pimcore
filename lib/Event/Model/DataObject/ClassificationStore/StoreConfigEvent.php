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
