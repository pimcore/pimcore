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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator;

use Pimcore\Localization\Locale;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;

class Published extends AbstractOperator
{

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->locale = $config->locale;
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
        if (method_exists($target, "setPublished")) {

            $published = $rowData[$colIndex] ? true : false;
            $target->setPublished($published);
        }
    }
}
