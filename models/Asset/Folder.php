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
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Model\Asset;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Folder extends Model\Asset
{
    /**
     * @var string
     */
    protected $type = 'folder';

    /**
     * Contains the child elements
     *
     * @var Asset[]
     */
    protected $children;

    /**
     * Indicator if there are children
     *
     * @var bool
     */
    protected $hasChildren;

    /**
     * set the children of the document
     *
     * @param Asset[] $children
     *
     * @return Folder
     */
    public function setChildren($children)
    {
        $this->children = $children;
        if (is_array($children) and count($children) > 0) {
            $this->hasChildren = true;
        } else {
            $this->hasChildren = false;
        }

        return $this;
    }

    /**
     * @return Asset[]|self[]
     */
    public function getChildren()
    {
        if ($this->children === null) {
            $list = new Asset\Listing();
            $list->setCondition('parentId = ?', $this->getId());
            $list->setOrderKey('filename');
            $list->setOrder('asc');

            $this->children = $list->getAssets();
        }

        return $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if (is_bool($this->hasChildren)) {
            if (($this->hasChildren and empty($this->children)) or (!$this->hasChildren and !empty($this->children))) {
                return $this->getDao()->hasChildren();
            } else {
                return $this->hasChildren;
            }
        }

        return $this->getDao()->hasChildren();
    }
}
