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

use Pimcore\Model\User;

/**
 * @method \Pimcore\Model\User\Listing\Dao getDao()
 * @method User[]|User\Folder[] load()
 */
class Listing extends Listing\AbstractListing
{
    protected string $type = 'user';

    /**
     * Alias for $this->getItems()
     *
     * @return \Pimcore\Model\User[]
     */
    public function getUsers(): array
    {
        return $this->getItems();
    }
}
