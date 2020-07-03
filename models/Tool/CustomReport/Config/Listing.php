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

namespace Pimcore\Model\Tool\CustomReport\Config;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\CustomReport\Config\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * @var Model\Tool\CustomReport\Config[]|null
     */
    protected $reports = null;

    /**
     * @return Model\Tool\CustomReport\Config[]
     */
    public function getReports()
    {
        if ($this->reports === null) {
            $this->getDao()->load();
        }

        return $this->reports;
    }

    /**
     * @param Model\Tool\CustomReport\Config[]|null $reports
     *
     * @return $this
     */
    public function setReports($reports)
    {
        $this->reports = $reports;

        return $this;
    }
}
