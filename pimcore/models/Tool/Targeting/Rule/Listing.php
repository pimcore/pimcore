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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Targeting\Rule;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Targeting\Rule\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of Tool\Targeting\Rule
     *
     * @var array
     */
    public $targets = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @param $key
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param $targets
     * @return $this
     */
    public function setTargets($targets)
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * @return array
     */
    public function getTargets()
    {
        return $this->targets;
    }
}
