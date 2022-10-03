<?php

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
    /**
     * @var array|null
     */
    protected $dirtyFields;

    /**
     * @return bool
     */
    public function hasDirtyFields()
    {
        return is_array($this->dirtyFields) && count($this->dirtyFields);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isFieldDirty($key)
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
    public function markFieldDirty($field, $dirty = true)
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

    public function resetDirtyMap()
    {
        $this->dirtyFields = null;
    }
}
