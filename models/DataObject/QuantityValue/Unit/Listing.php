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

namespace Pimcore\Model\DataObject\QuantityValue\Unit;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\QuantityValue\Unit\Listing\Dao getDao()
 * @method Model\DataObject\QuantityValue\Unit[] load()
 * @method Model\DataObject\QuantityValue\Unit|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return in_array($key, ['abbreviation', 'group', 'id', 'longname', 'baseunit', 'factor'], true);
    }

    /**
     * @return Model\DataObject\QuantityValue\Unit[]
     */
    public function getUnits()
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject\QuantityValue\Unit[]|null $units
     *
     * @return $this
     */
    public function setUnits($units)
    {
        return $this->setData($units);
    }
}
