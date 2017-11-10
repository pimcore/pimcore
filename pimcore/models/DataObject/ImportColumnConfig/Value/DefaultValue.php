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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ImportColumnConfig\Value;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;

class DefaultValue extends AbstractConfigElement
{
    /**
     * @param $element Concrete
     * @param $target
     * @param $rowData
     * @param $rowIndex
     *
     * @return null|\stdClass
     */
    public function process($element, &$target, &$rowData, $colIndex, &$context = [])
    {
        $class = $element->getClass();
        $fd = $class->getFieldDefinition($this->attribute);

        if (!$fd) {
            $lfDef = $class->getFieldDefinition("localizedfields");
            if ($lfDef) {
                $fd = $lfDef->getFieldDefinition($this->attribute);
            }
        }

        if ($fd) {
            // TODO switch between direct and fromCsvImport
            $data = $rowData[$colIndex];
            $data = $fd->getFromCsvImport($data);
            $setter = 'set' . ucfirst($this->attribute);
            $target->$setter($data);
        }

        return null;
    }
}
