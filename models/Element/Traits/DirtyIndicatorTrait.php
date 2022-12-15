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
    protected ?array $dirtyFields = null;

    public function hasDirtyFields(): bool
    {
        return is_array($this->dirtyFields) && count($this->dirtyFields);
    }

    public function isFieldDirty(string $key): bool
    {
        if (is_array($this->dirtyFields) && array_key_exists($key, $this->dirtyFields)) {
            return true;
        }

        return false;
    }

    /**
     * marks the given field as dirty
     *
     * @param string $field
     * @param bool $dirty
     */
    public function markFieldDirty(string $field, bool $dirty = true): void
    {
        if ($dirty && !is_array($this->dirtyFields)) {
            $this->dirtyFields = [];
        }

        if ($dirty) {
            $this->dirtyFields[$field] = true;
        } else {
            unset($this->dirtyFields[$field]);
        }
    }

    public function resetDirtyMap(): void
    {
        $this->dirtyFields = null;
    }
}
