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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore\KeyGroupRelation;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Classificationstore\KeyGroupRelation\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * Contains the results of the list.
     *
     * @var array
     */
    public $list = [];

    /** @var bool */
    public $resolveGroupName;

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
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
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array
     *
     * @return $this
     */
    public function setList($theList)
    {
        $this->list = $theList;

        return $this;
    }

    /**
     * @return bool
     */
    public function getResolveGroupName()
    {
        return $this->resolveGroupName;
    }

    /**
     * @param bool $resolveGroupName
     */
    public function setResolveGroupName($resolveGroupName)
    {
        $this->resolveGroupName = $resolveGroupName;
    }
}
