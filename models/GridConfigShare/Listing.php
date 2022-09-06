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
 * @method Model\GridConfigShare|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\GridConfigShare[]
     */
    public function getGridconfigShares()
    {
        return $this->getData();
    }

    /**
     * @param Model\GridConfigShare[]|null $gridconfigShares
     *
     * @return $this
     */
    public function setGridconfigShares($gridconfigShares)
    {
        return $this->setData($gridconfigShares);
    }
}
