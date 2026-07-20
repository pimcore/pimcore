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

namespace Pimcore\Model\Tool\Email\Blocklist;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Blocklist\Listing\Dao getDao()
 * @method void delete()*
 * @method Model\Tool\Email\Blocklist[] load()
 * @method Model\Tool\Email\Blocklist|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Tool\Email\Blocklist[]|null $items
     *
     * @return $this
     */
    public function setItems(?array $items): static
    {
        return $this->setData($items);
    }

    /**
     * @return Model\Tool\Email\Blocklist[]
     */
    public function getItems(): array
    {
        return $this->getData();
    }
}
