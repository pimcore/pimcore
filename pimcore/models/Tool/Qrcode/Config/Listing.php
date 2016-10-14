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
 * @package    Property
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Qrcode\Config;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Qrcode\Config\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{

    /**
     * @var array
     */
    public $codes = [];

    /**
     * @return array
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * @param $codes
     * @return $this
     */
    public function setCodes($codes)
    {
        $this->codes = $codes;

        return $this;
    }
}
