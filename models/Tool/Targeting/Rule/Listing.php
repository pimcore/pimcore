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
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Targeting\Rule;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\Rule;

/**
 * @method Listing\Dao getDao()
 * @method Rule[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Rule[]|null
     */
    protected $targets = null;

    /**
     * @param Rule[] $targets
     *
     * @return $this
     */
    public function setTargets(array $targets)
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * @return Rule[]
     */
    public function getTargets(): array
    {
        if ($this->targets === null) {
            $this->getDao()->load();
        }

        return $this->targets;
    }
}
