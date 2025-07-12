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

namespace Pimcore\Model\Version;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Version\Listing\Dao getDao()
 * @method int[] loadIdList()
 * @method Model\Version[] load()
 * @method Model\Version|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @internal
     */
    protected bool $loadAutoSave = false;

    public function isLoadAutoSave(): bool
    {
        return $this->loadAutoSave;
    }

    /**
     * @return $this
     */
    public function setLoadAutoSave(bool $loadAutoSave): static
    {
        $this->loadAutoSave = $loadAutoSave;

        return $this;
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\Version[]|null $versions
     *
     * @return $this
     */
    public function setVersions(?array $versions): static
    {
        return $this->setData($versions);
    }
}
