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

namespace Pimcore\Model\Tool\Targeting\TargetGroup;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @internal
 *
 * @method Listing\Dao getDao()
 * @method TargetGroup[] load()
 * @method TargetGroup|false current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param TargetGroup[] $targetGroups
     *
     * @return $this
     */
    public function setTargetGroups(array $targetGroups)
    {
        return $this->setData($targetGroups);
    }

    /**
     * @return TargetGroup[]
     */
    public function getTargetGroups(): array
    {
        return $this->getData();
    }
}
