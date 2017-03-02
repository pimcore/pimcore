<?php

namespace Pimcore\Event\System;

use DI\ContainerBuilder;
use Symfony\Component\EventDispatcher\Event;

class PhpDiBuilderEvent extends Event {

    /**
     * @var ContainerBuilder
     */
    protected $builder;

    /**
     * PhpDiBuilderEvent constructor.
     * @param ContainerBuilder $builder
     */
    public function __construct(ContainerBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return ContainerBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param ContainerBuilder $builder
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
    }
}