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
     * Contains the results of the list
     *
     * @var Rule[]
     */
    public $targets = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

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
        return $this->targets;
    }
}
