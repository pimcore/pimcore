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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    protected $o_dirtyFields;

    /**
     * @return bool
     */
    public function hasDirtyFields()
    {
        return is_array($this->o_dirtyFields) && count($this->o_dirtyFields);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isFieldDirty($key)
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
    public function markFieldDirty($field, $dirty = true)
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

    public function resetDirtyMap()
    {
        $this->o_dirtyFields = null;
    }
}
