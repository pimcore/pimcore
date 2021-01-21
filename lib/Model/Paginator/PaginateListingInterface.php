<?php

namespace Pimcore\Model\Paginator;

use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;

/**
 * @TODO: Pimcore 10 : Remove AdapterInterface and AdapterAggregateInterface
 */
interface PaginateListingInterface extends \Countable, \Iterator, AdapterInterface, AdapterAggregateInterface
{
    /**
     * Returns a collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage);
}
