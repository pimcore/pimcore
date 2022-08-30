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

namespace Pimcore\Model\Tool\Targeting\Rule;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\Rule;

/**
 * @internal
 *
 * @method Listing\Dao getDao()
 * @method Rule[] load()
 * @method Rule|false current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Rule[] $targets
     *
     * @return $this
     */
    public function setTargets(array $targets)
    {
        return $this->setData($targets);
    }

    /**
     * @return Rule[]
     */
    public function getTargets(): array
    {
        return $this->getData();
    }
}
