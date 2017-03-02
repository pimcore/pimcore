<?php

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\GroupConfig;
use Symfony\Component\EventDispatcher\Event;

class GroupConfigEvent extends Event {

    /**
     * @var GroupConfig
     */
    protected $groupConfig;

    /**
     * DocumentEvent constructor.
     * @param GroupConfig $groupConfig
     */
    function __construct(GroupConfig $groupConfig)
    {
        $this->groupConfig = $groupConfig;
    }

    /**
     * @return GroupConfig
     */
    public function getGroupConfig()
    {
        return $this->groupConfig;
    }

    /**
     * @param GroupConfig $groupConfig
     */
    public function setGroupConfig($groupConfig)
    {
        $this->groupConfig = $groupConfig;
    }
}
