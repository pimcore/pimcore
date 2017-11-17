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
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;

class DefaultValue extends AbstractConfigElement
{

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->mode = $config->mode;
        $this->doNotOverwrite = $config->doNotOverwrite;
        $this->skipEmptyValues = $config->skipEmptyValues;
    }

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


        if ($target instanceof Concrete) {
            $realAttribute = $this->attribute;
            $container = $target->getClass();
        } else if ($target instanceof AbstractData) {
            $keyParts = explode("~", $this->attribute);
            $brickType = $keyParts[0];
            $realAttribute = $keyParts[1];
            $container = Definition::getByKey($brickType);
        }

        $fd = $container->getFieldDefinition($realAttribute);

        if (!$fd) {
            $lfDef = $container->getFieldDefinition('localizedfields');
            if ($lfDef) {
                $fd = $lfDef->getFieldDefinition($realAttribute);
            }
        }

        if ($fd) {
            $data = $rowData[$colIndex];

            if ($this->skipEmptyValues && !$data) {
                return;
            }

            if (!$this->mode != "direct") {
                $data = $fd->getFromCsvImport($data);
            }
            $setter = 'set' . ucfirst($realAttribute);

            if ($this->doNotOverwrite) {
                $getter = 'get' . ucfirst($realAttribute);
                $currentValue = $target->$getter();
                if ($currentValue) {
                    return;
                }
            }

            $target->$setter($data);
        }

    }
}
