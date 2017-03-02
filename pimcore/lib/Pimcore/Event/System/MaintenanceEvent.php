<?php

namespace Pimcore\Event\System;

use Pimcore\Model\Schedule\Manager\Procedural;
use Symfony\Component\EventDispatcher\Event;

class MaintenanceEvent extends Event {

    /**
     * @var Procedural
     */
    protected $manager;

    /**
     * MaintenanceEvent constructor.
     * @param Procedural $manager
     */
    public function __construct(Procedural $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return Procedural
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Procedural $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }
}
