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

namespace Pimcore\Model\DataObject\Classificationstore\GroupConfig;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing\Dao getDao()
 * @method Model\DataObject\Classificationstore\GroupConfig[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * Contains the results of the list.
     *
     * @var array
     */
    public $list = [];

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
}
