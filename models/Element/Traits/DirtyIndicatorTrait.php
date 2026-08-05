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

namespace Pimcore\Model\Element\Traits;

/**
 * @internal
 */
trait DirtyIndicatorTrait
{
    /** @var array<string, true> */
    protected array $dirtyFields = [];

    public function hasDirtyFields(): bool
    {
        return count($this->dirtyFields) !== 0;
    }

    public function isFieldDirty(string $key): bool
    {
        return $this->dirtyFields[$key] ?? false;
    }

    /**
     * marks the given field as dirty
     */
    public function markFieldDirty(string $field, bool $dirty = true): void
    {
        if ($dirty) {
            $this->dirtyFields[$field] = true;
        } else {
            unset($this->dirtyFields[$field]);
        }
    }

    public function resetDirtyMap(): void
    {
        $this->dirtyFields = [];
    }

    /**
     * @return string[]
     */
    public function getDirtyFields(): array
    {
        return array_keys($this->dirtyFields);
    }
}
