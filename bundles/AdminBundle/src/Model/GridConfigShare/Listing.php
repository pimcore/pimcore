<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Model\GridConfigShare;

use Pimcore\Bundle\AdminBundle\Model\GridConfigShare;
use Pimcore\Model;

/**
 * @method GridConfigShare\Listing\Dao getDao()
 * @method GridConfigShare[] load()
 * @method GridConfigShare|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return GridConfigShare[]
     */
    public function getGridconfigShares(): array
    {
        return $this->getData();
    }

    /**
     * @param GridConfigShare[]|null $gridconfigShares
     *
     * @return $this
     */
    public function setGridconfigShares(?array $gridconfigShares): static
    {
        return $this->setData($gridconfigShares);
    }
}
