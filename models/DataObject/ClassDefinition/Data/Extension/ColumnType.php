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
     * @var string | array
     */
    public $columnType;

    /**
     * @return string | array
     */
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * @deprecated
     * @param string | array $columnType
     * @return $this
     */
    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;

        return $this;
    }
}
