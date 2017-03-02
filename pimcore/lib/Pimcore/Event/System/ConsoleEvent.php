<?php

namespace Pimcore\Event\System;

use Pimcore\Console\Application;
use Symfony\Component\EventDispatcher\Event;

class ConsoleEvent extends Event {

    /**
     * @var Application
     */
    protected $application;

    /**
     * ConsoleEvent constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }
}