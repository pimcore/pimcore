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

namespace Pimcore\Model\User;

/**
 * @method \Pimcore\Model\User\Dao getDao()
 */
class Folder extends UserRole\Folder
{
    protected string $type = 'userfolder';

    public function getChildren(): array
    {
        if ($this->children === null) {
            if ($this->getId()) {
                $list = new Listing();
                $list->setCondition('parentId = ?', $this->getId());

                $this->children = $list->getUsers();
            } else {
                $this->children = [];
            }
        }

        return $this->children;
    }
}
