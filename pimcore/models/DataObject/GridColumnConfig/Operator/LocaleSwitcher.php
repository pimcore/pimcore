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

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

use Pimcore\Localization\Locale;

class LocaleSwitcher extends AbstractOperator
{
    protected $locale;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->locale = $config->locale;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];
            $container = \Pimcore::getContainer();
            $localeService = $container->get(Locale::class);
            $currentLocale = $localeService->getLocale();

            $localeService->setLocale($this->locale);

            $result = $c->getLabeledValue($element);

            $localeService->setLocale($currentLocale);
        }

        return $result;
    }
}
