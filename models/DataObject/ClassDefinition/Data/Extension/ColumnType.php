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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

trait ColumnType
{
    /**
     * @return string|array|null
     */
    public function getColumnType()
    {
        if (property_exists($this, 'columnType')) {
            return $this->columnType;
        }

        return null;
    }

    /**
     * @param string | array $columnType
     *
     * @return $this
     */
    public function setColumnType($columnType)
    {
        if (property_exists($this, 'columnType')) {
            $this->columnType = $columnType;
        }

        return $this;
    }
}
