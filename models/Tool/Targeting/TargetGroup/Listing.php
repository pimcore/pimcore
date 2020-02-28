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

namespace Pimcore\Model\Tool\Targeting\TargetGroup;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @method Listing\Dao getDao()
 * @method TargetGroup[] load()
 * @method TargetGroup current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var TargetGroup[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $targetGroups = null;

    public function __construct()
    {
        $this->targetGroups = & $this->data;
    }

    /**
     * @param TargetGroup[] $targetGroups
     *
     * @return static
     */
    public function setTargetGroups(array $targetGroups)
    {
        return $this->setData($targetGroups);
    }

    /**
     * @return TargetGroup[]
     */
    public function getTargetGroups(): array
    {
        return $this->getData();
    }
}
