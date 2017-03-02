<?php

namespace Pimcore\Event\Model;

use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\EventDispatcher\Event;

class SearchBackendEvent extends Event {

    /**
     * @var Data
     */
    protected $data;

    /**
     * Data constructor.
     * @param Data $data
     */
    function __construct(Data $data)
    {
        $this->data = $data;
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Data $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
