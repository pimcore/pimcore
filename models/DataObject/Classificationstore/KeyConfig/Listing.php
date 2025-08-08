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

namespace Pimcore\Model\DataObject\Classificationstore\KeyConfig;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing\Dao getDao()
 * @method Model\DataObject\Classificationstore\KeyConfig[] load()
 * @method Model\DataObject\Classificationstore\KeyConfig|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    protected bool $includeDisabled = false;

    /**
     * @return Model\DataObject\Classificationstore\KeyConfig[]
     */
    public function getList(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject\Classificationstore\KeyConfig[]|null $theList
     *
     * @return $this
     */
    public function setList(?array $theList): static
    {
        return $this->setData($theList);
    }

    public function getIncludeDisabled(): bool
    {
        return $this->includeDisabled;
    }

    public function setIncludeDisabled(bool $includeDisabled): void
    {
        $this->includeDisabled = $includeDisabled;
    }
}
