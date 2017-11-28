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

class LocaleSwitcher extends AbstractOperator
{
    protected $locale;

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
        $container = \Pimcore::getContainer();
        $localeService = $container->get(Locale::class);
        $currentLocale = $localeService->getLocale();

        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            /** @var $child AbstractConfigElement */
            foreach ($childs as $child) {
                $localeService->setLocale($this->locale);
                $child->process($element, $target, $rowData, $colIndex, $context);
            }
        }

        $localeService->setLocale($currentLocale);
    }
}
