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
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @deprecated
 */
abstract class JsonListing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    public function getFilter()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'FilterListingTrait')
        );

        return $this->filter;
    }

    public function setFilter($filter)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'FilterListingTrait')
        );
        $this->filter = $filter;
    }

    public function getOrder()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'OrderListingTrait')
        );

        return $this->order;
    }

    public function setOrder($order)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'OrderListingTrait')
        );
        $this->order = $order;
    }
}
