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

namespace Pimcore\Model\User\Role;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\Role\Listing\Dao getDao()
 */
class Listing extends Model\User\Listing\AbstractListing
{
    protected string $type = 'role';

    /**
     * Alias for $this->getItems()
     *
     * @return \Pimcore\Model\User\Role[]
     */
    public function getRoles(): array
    {
        return $this->getItems();
    }
}
