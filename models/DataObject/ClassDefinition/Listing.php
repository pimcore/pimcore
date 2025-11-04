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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition[] load()
 * @method Model\DataObject\ClassDefinition|false current()
 */
class Listing extends Model\Listing\AbstractListing
{
    private bool $force = false;

    /**
     * @return Model\DataObject\ClassDefinition[]
     */
    public function getClasses(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject\ClassDefinition[]|null $classes
     *
     * @return $this
     */
    public function setClasses(?array $classes): static
    {
        return $this->setData($classes);
    }

    public function setForce(bool $force): Listing
    {
        $this->force = $force;

        return $this;
    }

    public function getForce(): bool
    {
        return $this->force;
    }
}
