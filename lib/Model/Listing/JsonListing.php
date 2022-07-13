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
use Pimcore\Model\Listing\Traits\FilterListingTrait;

abstract class JsonListing extends AbstractModel
{
    use FilterListingTrait;

    /**
     * @var callable|null
     */
    protected $order;

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
