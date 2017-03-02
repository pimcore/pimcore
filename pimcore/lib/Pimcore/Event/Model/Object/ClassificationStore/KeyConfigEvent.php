<?php

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\Event;

class KeyConfigEvent extends Event {

    /**
     * @var KeyConfig
     */
    protected $keyConfig;

    /**
     * DocumentEvent constructor.
     * @param KeyConfig $keyConfig
     */
    function __construct(KeyConfig $keyConfig)
    {
        $this->keyConfig = $keyConfig;
    }

    /**
     * @return KeyConfig
     */
    public function getKeyConfig()
    {
        return $this->keyConfig;
    }

    /**
     * @param KeyConfig $keyConfig
     */
    public function setKeyConfig($keyConfig)
    {
        $this->keyConfig = $keyConfig;
    }
}
