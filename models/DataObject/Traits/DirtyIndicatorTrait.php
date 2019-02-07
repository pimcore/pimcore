<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

trait DirtyIndicatorTrait
{
    /**
     * @var array
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
     * @param $key
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
     * @param $field
     * @param bool $dirty
     *
     * @return mixed
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
