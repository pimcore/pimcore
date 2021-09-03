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

namespace Pimcore\Model\Dao;

/**
 * @internal
 */
trait AutoIncrementTrait
{
    public function getNextId($listingClass): int
    {
        $listing = new $listingClass();
        $listing = $listing->load();

        $ids = array_map(function ($item) {
            return (int) $item->getId();
        }, $listing);

        return max($ids) + 1;
    }
}
