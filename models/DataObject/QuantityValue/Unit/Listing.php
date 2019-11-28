<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\QuantityValue\Unit;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\QuantityValue\Unit\Listing\Dao getDao()
 * @method Model\DataObject\QuantityValue\Unit[] load()
 * @method Model\DataObject\QuantityValue\Unit current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $units = null;

    public function __construct()
    {
        $this->units =& $this->data;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if ($key == 'abbreviation' || $key == 'group' || $key == 'id' || $key == 'longname') {
            return true;
        }

        return false;
    }

    /**
     * @return Model\DataObject\QuantityValue\Unit[]
     */
    public function getUnits()
    {
        return $this->getData();
    }

    /**
     * @param array $units
     */
    public function setUnits($units)
    {
        return $this->setData($units);
    }
}
