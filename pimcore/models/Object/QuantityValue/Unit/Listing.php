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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\QuantityValue\Unit;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\QuantityValue\Unit\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * @var array
     */
    public $units;

    /**
     * @param $key
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        if ($key == "abbreviation" || $key == "group" || $key == "id" || $key == "longname") {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getUnits()
    {
        if (empty($this->units)) {
            $this->load();
        }

        return $this->units;
    }

    /**
     * @param array $units
     */
    public function setUnits($units)
    {
        $this->units = $units;
    }
}
