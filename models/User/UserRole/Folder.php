<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;
use Pimcore\Model\User\Role;

/**
 * @method \Pimcore\Model\User\UserRole\Dao getDao()
 */
class Folder extends Model\User\AbstractUser
{
    use Model\Element\ChildsCompatibilityTrait;

    /**
     * @internal
     *
     * @var array
     */
    protected $children = [];

    /**
     * @internal
     *
     * @var bool
     */
    protected $hasChilds;

    /**
     * Returns true if the document has at least one child
     *
     * @return bool
     */
    public function hasChildren()
    {
        if ($this->hasChilds !== null) {
            return $this->hasChilds;
        }

        return $this->getDao()->hasChildren();
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        if (empty($this->children)) {
            $list = new Role\Listing();
            $list->setCondition('parentId = ?', $this->getId());

            $this->children = $list->getRoles();
        }

        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;
        if (is_array($children) and count($children) > 0) {
            $this->hasChilds = true;
        } else {
            $this->hasChilds = false;
        }

        return $this;
    }
}
