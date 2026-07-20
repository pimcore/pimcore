<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;
use Pimcore\Model\User\Role;

/**
 * @method \Pimcore\Model\User\UserRole\Dao getDao()
 */
class Folder extends Model\User\AbstractUser
{
    /**
     * @internal
     *
     */
    protected ?array $children = null;

    /**
     * @internal
     *
     */
    protected ?bool $hasChildren = null;

    /**
     * Returns true if the document has at least one child
     *
     */
    public function hasChildren(): bool
    {
        if ($this->hasChildren === null) {
            $this->hasChildren = $this->getDao()->hasChildren();
        }

        return $this->hasChildren;
    }

    public function getChildren(): array
    {
        if ($this->children === null) {
            if ($this->getId()) {
                $list = new Role\Listing();
                $list->setCondition('parentId = ?', $this->getId());

                $this->children = $list->getRoles();
            } else {
                $this->children = [];
            }
        }

        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        $this->hasChildren = count($children) > 0;

        return $this;
    }
}
