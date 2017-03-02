<?php

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\StoreConfig;
use Symfony\Component\EventDispatcher\Event;

class StoreConfigEvent extends Event {

    /**
     * @var StoreConfig
     */
    protected $storeConfig;

    /**
     * DocumentEvent constructor.
     * @param StoreConfig $storeConfig
     */
    function __construct(StoreConfig $storeConfig)
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

