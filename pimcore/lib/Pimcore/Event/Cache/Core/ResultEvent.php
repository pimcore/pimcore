<?php

namespace Pimcore\Event\Cache\Core;

use Symfony\Component\EventDispatcher\Event;

class ResultEvent extends Event
{
    /**
     * @var bool
     */
    protected $result = true;

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
        $this->result = $result;
    }
}
