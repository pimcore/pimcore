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

namespace Pimcore\Model\GridConfigShare;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\GridConfigShare\Listing\Dao getDao()
 * @method Model\GridConfigShare[] load()
 * @method Model\GridConfigShare current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $gridConfigShares = null;

    public function __construct()
    {
        $this->gridConfigShares = & $this->data;
    }

    /**
     * @return Model\GridConfigShare[]
     */
    public function getGridconfigShares()
    {
        return $this->getData();
    }

    /**
     * @param array $gridconfigShares
     */
    public function setGridconfigShares($gridconfigShares)
    {
        return $this->setData($gridconfigShares);
    }
}
