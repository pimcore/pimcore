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

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

use Pimcore\DataObject\Import\ColumnConfig\AbstractConfigElement;

/**
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
class Base64 extends AbstractOperator
{
    /**
     * @var string
     */
    private $mode;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->mode = $config->mode;
    }

    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        $originalCellData = $rowData[$colIndex];

        $celldata = '';
        if ($this->mode == 'd') {
            $celldata = base64_decode($originalCellData);
        } elseif ($this->mode == 'e') {
            $celldata = base64_encode($originalCellData);
        }

        $rowData[$colIndex] = $celldata;

        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            for ($i = 0; $i < count($childs); $i++) {
                /** @var AbstractConfigElement $child */
                $child = $childs[$i];
                $child->process($element, $target, $rowData, $colIndex, $context);
            }
        }

        $rowData[$colIndex] = $originalCellData;
    }
}
