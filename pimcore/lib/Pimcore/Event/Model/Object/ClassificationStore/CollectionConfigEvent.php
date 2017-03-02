<?php

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\CollectionConfig;
use Symfony\Component\EventDispatcher\Event;

class CollectionConfigEvent extends Event {

    /**
     * @var CollectionConfig
     */
    protected $collectionConfig;

    /**
     * DocumentEvent constructor.
     * @param CollectionConfig $collectionConfig
     */
    function __construct(CollectionConfig $collectionConfig)
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
