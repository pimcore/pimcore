<?php

namespace Pimcore\Event\Model\Object;

use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\EventDispatcher\Event;

class CustomLayoutEvent extends Event {

    /**
     * @var ClassDefinition\CustomLayout
     */
    protected $customLayout;

    /**
     * DocumentEvent constructor.
     * @param ClassDefinition\CustomLayout $customLayout
     */
    function __construct(ClassDefinition\CustomLayout $customLayout)
    {
        $this->customLayout = $customLayout;
    }

    /**
     * @return ClassDefinition\CustomLayout
     */
    public function getCustomLayout()
    {
        return $this->customLayout;
    }

    /**
     * @param ClassDefinition\CustomLayout $customLayout
     */
    public function setCustomLayout($customLayout)
    {
        $this->customLayout = $customLayout;
    }
}
