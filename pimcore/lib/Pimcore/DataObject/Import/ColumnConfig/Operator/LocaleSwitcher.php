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

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

use Pimcore\DataObject\Import\ColumnConfig\AbstractConfigElement;
use Pimcore\Localization\Locale;

class LocaleSwitcher extends AbstractOperator
{
    /**
     * @var Locale
     */
    private $localeService;

    /**
     * @var string
     */
    private $locale;

    public function __construct(Locale $localeService, \stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->localeService = $localeService;
        $this->locale        = (string)$config->locale;
    }

    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        $currentLocale = $this->localeService->getLocale();

        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            /** @var $child AbstractConfigElement */
            foreach ($childs as $child) {
                $this->localeService->setLocale($this->locale);

                $child->process($element, $target, $rowData, $colIndex, $context);
            }
        }

        $this->localeService->setLocale($currentLocale);
    }
}
