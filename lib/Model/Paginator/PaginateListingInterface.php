<?php

namespace Pimcore\Model\Paginator;

interface PaginateListingInterface extends \Countable, \Iterator
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
