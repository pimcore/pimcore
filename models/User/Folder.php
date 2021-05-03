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

namespace Pimcore\Model\User;

/**
 * @method \Pimcore\Model\User\Dao getDao()
 */
class Folder extends UserRole\Folder
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'userfolder';

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        if (empty($this->children)) {
            $list = new Listing();
            $list->setCondition('parentId = ?', $this->getId());

            $this->children = $list->getUsers();
        }

        return $this->children;
    }
}
