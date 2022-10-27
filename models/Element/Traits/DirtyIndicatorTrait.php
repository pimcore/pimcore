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
    protected ?array $o_dirtyFields = null;

    public function hasDirtyFields(): bool
    {
        return is_array($this->o_dirtyFields) && count($this->o_dirtyFields);
    }

    public function isFieldDirty(string $key): bool
    {
        if (is_array($this->o_dirtyFields) && array_key_exists($key, $this->o_dirtyFields)) {
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
        if ($dirty && !is_array($this->o_dirtyFields)) {
            $this->o_dirtyFields = [];
        }

        if ($dirty) {
            $this->o_dirtyFields[$field] = true;
        } else {
            unset($this->o_dirtyFields[$field]);
        }
    }

    public function resetDirtyMap(): void
    {
        $this->o_dirtyFields = null;
    }
}
