<?php

namespace Pimcore\Event\Model\Object;

use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\EventDispatcher\Event;

class ClassDefinitionEvent extends Event {

    /**
     * @var ClassDefinition
     */
    protected $classDefinition;

    /**
     * DocumentEvent constructor.
     * @param ClassDefinition $classDefinition
     */
    function __construct(ClassDefinition $classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }

    /**
     * @return ClassDefinition
     */
    public function getClassDefinition()
    {
        return $this->classDefinition;
    }

    /**
     * @param ClassDefinition $classDefinition
     */
    public function setClassDefinition($classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }
}
