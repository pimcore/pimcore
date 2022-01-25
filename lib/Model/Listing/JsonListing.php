<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Listing;

use Pimcore\Model\AbstractModel;

abstract class JsonListing extends AbstractModel
{
    /**
     * @var callable|null
     */
    protected $filter;

    /**
     * @var callable|null
     */
    protected $order;

    /**
     * @return callable|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param callable|null $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return callable|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param callable|null $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}
