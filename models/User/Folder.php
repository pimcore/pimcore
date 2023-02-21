<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
