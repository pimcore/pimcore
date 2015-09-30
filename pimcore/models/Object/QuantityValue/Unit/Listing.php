<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\QuantityValue\Unit;

use Pimcore\Model;


class Listing extends Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $units;

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "abbreviation" || $key == "group" || $key == "id" || $key == "longname") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getUnits() {
        if(empty($this->units)) {
            $this->load();
        }
        return $this->units;
    }

    /**
     * @param array $units
     * @return void
     */
    function setUnits($units) {
        $this->units = $units;
    }

}
