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

namespace Pimcore\Model\DataObject\SelectOptions\Config;

use ArrayIterator;
use IteratorAggregate;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\SelectOptions\Config\Listing\Dao getDao()
 */
class Listing extends Model\AbstractModel implements IteratorAggregate
{
    /**
     * @internal
     *
     * @var Model\DataObject\SelectOptions\Config[]|null
     */
    protected ?array $selectOptions = null;

    /**
     * @return Model\DataObject\SelectOptions\Config[]
     */
    public function getSelectOptions(): array
    {
        if ($this->selectOptions === null) {
            $this->getDao()->loadList();
        }

        return $this->selectOptions;
    }

    /**
     * @param Model\DataObject\SelectOptions\Config[]|null $selectOptions
     *
     * @return $this
     */
    public function setSelectOptions(?array $selectOptions): static
    {
        $this->selectOptions = $selectOptions;

        return $this;
    }

    /**
     * Alias of getSelectOptions()
     *
     * @return Model\DataObject\SelectOptions\Config[]
     */
    public function load(): array
    {
        return $this->getSelectOptions();
    }

    /**
     * @return ArrayIterator<\Pimcore\Model\DataObject\SelectOptions\Config>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getSelectOptions());
    }

    public function hasConfig(?string $id): bool
    {
        if (empty($id)) {
            return false;
        }

        $matchId = strtolower($id);
        foreach ($this as $selectOptionConfig) {
            if (strtolower($selectOptionConfig->getId()) === $matchId) {
                return true;
            }
        }

        return false;
    }
}
