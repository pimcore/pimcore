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

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\StoreConfig;
use Symfony\Component\EventDispatcher\Event;

class StoreConfigEvent extends Event
{
    /**
     * @var StoreConfig
     */
    protected $storeConfig;

    /**
     * DocumentEvent constructor.
     *
     * @param StoreConfig $storeConfig
     */
    public function __construct(StoreConfig $storeConfig)
    {
        $this->storeConfig = $storeConfig;
    }

    /**
     * @return StoreConfig
     */
    public function getStoreConfig()
    {
        return $this->storeConfig;
    }

    /**
     * @param StoreConfig $storeConfig
     */
    public function setStoreConfig($storeConfig)
    {
        $this->storeConfig = $storeConfig;
    }
}
