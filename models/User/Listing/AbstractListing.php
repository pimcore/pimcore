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

namespace Pimcore\Model\User\Listing;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\User\Listing\AbstractListing\Dao getDao()
 * @method Model\User[] load()
 * @method Model\User|false current()
 * @method int getTotalCount()
 */
abstract class AbstractListing extends Model\Listing\AbstractListing
{
    protected string $type;

    public function getItems(): array
    {
        return $this->getData();
    }

    /**
     * @return $this
     */
    public function setItems(array $items): static
    {
        return $this->setData($items);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
