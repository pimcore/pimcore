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

namespace Pimcore\Model\Listing\Traits;

trait FilterListingTrait
{
    /**
     * @var callable|null
     */
    protected $filter;

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
}
