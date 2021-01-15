<?php

namespace Pimcore\Model\Paginator;

use Pimcore\Model\Paginator\Adapter\AdapterInterface;

interface AdapterAggregateInterface
{
    /**
     * Return a fully configured Paginator Adapter from this method.
     *
     * @return AdapterInterface
     */
    public function getPaginatorAdapter();
}
