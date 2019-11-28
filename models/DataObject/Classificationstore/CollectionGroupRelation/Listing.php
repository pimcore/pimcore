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

namespace Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing\Dao getDao()
 * @method Model\DataObject\Classificationstore\CollectionGroupRelation[] load()
 * @method Model\DataObject\Classificationstore\CollectionGroupRelation current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $list = null;

    public function __construct()
    {
        $this->list =& $this->data;
    }

    /**
     * @return Model\DataObject\Classificationstore\CollectionGroupRelation[]
     */
    public function getList()
    {
        return $this->getData();
    }

    /**
     * @param array
     *
     * @return $this
     */
    public function setList($theList)
    {
        return $this->setData($theList);
    }
}
