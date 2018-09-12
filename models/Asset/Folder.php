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
    use Model\Element\ChildsCompatibilityTrait;

    /**
     * @var string
     */
    protected $type = 'folder';

    /**
     * Contains the child elements
     *
     * @var array
     */
    protected $childs;

    /**
     * Indicator if there are childs
     *
     * @var bool
     */
    protected $hasChilds;

    /**
     * set the children of the document
     *
     * @param $children
     *
     * @return Folder
     */
    public function setChildren($children)
    {
        $this->childs = $children;
        if (is_array($children) and count($children) > 0) {
            $this->hasChilds = true;
        } else {
            $this->hasChilds = false;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        if ($this->childs === null) {
            $list = new Asset\Listing();
            $list->setCondition('parentId = ?', $this->getId());
            $list->setOrderKey('filename');
            $list->setOrder('asc');

            $this->childs = $list->load();
        }

        return $this->childs;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if (is_bool($this->hasChilds)) {
            if (($this->hasChilds and empty($this->childs)) or (!$this->hasChilds and !empty($this->childs))) {
                return $this->getDao()->hasChildren();
            } else {
                return $this->hasChilds;
            }
        }

        return $this->getDao()->hasChildren();
    }
}
