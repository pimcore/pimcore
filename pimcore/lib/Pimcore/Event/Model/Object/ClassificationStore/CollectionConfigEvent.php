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

use Pimcore\Model\Object\Classificationstore\CollectionConfig;
use Symfony\Component\EventDispatcher\Event;

class CollectionConfigEvent extends Event
{

    /**
     * @var CollectionConfig
     */
    protected $collectionConfig;

    /**
     * DocumentEvent constructor.
     * @param CollectionConfig $collectionConfig
     */
    public function __construct(CollectionConfig $collectionConfig)
    {
        $this->collectionConfig = $collectionConfig;
    }

    /**
     * @return CollectionConfig
     */
    public function getCollectionConfig()
    {
        return $this->collectionConfig;
    }

    /**
     * @param CollectionConfig $collectionConfig
     */
    public function setCollectionConfig($collectionConfig)
    {
        $this->collectionConfig = $collectionConfig;
    }
}
