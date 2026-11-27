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

namespace Pimcore\Model\User\Folder;

use Pimcore\Model;
use Pimcore\Model\User;

/**
 * Lists Pimcore user folders (type "userfolder") only.
 *
 * @method \Pimcore\Model\User\Folder\Listing\Dao getDao()
 * @method User\Folder[] load()
 */
class Listing extends Model\User\Listing\AbstractListing
{
    protected string $type = 'userfolder';

    /**
     * Alias for $this->getItems()
     *
     * @return User\Folder[]
     */
    public function getFolders(): array
    {
        return $this->getItems();
    }
}
