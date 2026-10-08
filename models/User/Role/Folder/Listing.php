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

namespace Pimcore\Model\User\Role\Folder;

use Pimcore\Model;
use Pimcore\Model\User\Role;

/**
 * Lists Pimcore user role folders (type "rolefolder") only.
 *
 * @method \Pimcore\Model\User\Role\Folder\Listing\Dao getDao()
 * @method Role\Folder[] load()
 */
class Listing extends Model\User\Listing\AbstractListing
{
    protected string $type = 'rolefolder';

    /**
     * Alias for $this->getItems()
     *
     * @return Role\Folder[]
     */
    public function getFolders(): array
    {
        return $this->getItems();
    }
}
