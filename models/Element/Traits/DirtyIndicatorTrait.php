<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
