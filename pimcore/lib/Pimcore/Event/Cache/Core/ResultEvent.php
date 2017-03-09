<?php

namespace Pimcore\Event\Cache\Core;

use Symfony\Component\EventDispatcher\Event;

class ResultEvent extends Event
{
    /**
     * @var bool
     */
    protected $result;

    /**
     * @param bool $result
     */
    public function __construct($result = true)
    {
        $this->setResult($result);
    }

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param bool $result
     */
    public function setResult($result)
    {
        $this->result = (bool)$result;
    }
}
