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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Qrcode\Config;

use Pimcore\Model;

/**
 * @deprecated
 *
 * @method \Pimcore\Model\Tool\Qrcode\Config\Listing\Dao getDao()
 * @method \Pimcore\Model\Tool\Qrcode\Config[] load()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * @var Model\Tool\Qrcode\Config[]|null
     */
    protected $codes = null;

    /**
     * @return Model\Tool\Qrcode\Config[]
     */
    public function getCodes()
    {
        if ($this->codes === null) {
            $this->getDao()->load();
        }

        return $this->codes;
    }

    /**
     * @param Model\Tool\Qrcode\Config[]|null $codes
     *
     * @return $this
     */
    public function setCodes($codes)
    {
        $this->codes = $codes;

        return $this;
    }
}
