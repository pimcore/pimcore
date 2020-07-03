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
 * @method Rule current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Rule[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $targets = null;

    public function __construct()
    {
        $this->targets = & $this->data;
    }

    /**
     * @param Rule[] $targets
     *
     * @return static
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
