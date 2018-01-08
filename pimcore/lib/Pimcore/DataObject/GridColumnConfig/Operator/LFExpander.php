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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Localization\Locale;
use Pimcore\Tool;

class LFExpander extends AbstractOperator
{
    /**
     * @var Locale
     */
    private $localeService;

    private $locales;
    private $asArray;
    private $prefix;

    public function __construct(Locale $localeService, \stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->localeService = $localeService;

        $this->prefix = $config->prefix;
        $this->locales = $config->locales;
        $this->asArray = $config->asArray;
    }

    public function getLabeledValue($element)
    {
        $childs = $this->getChilds();
        if ($childs[0]) {
            if ($this->getAsArray()) {
                $result = new ResultContainer();
                $result->label = $this->label;
                $resultValues = [];

                $currentLocale = $this->localeService->getLocale();

                $validLanguages = $this->getValidLanguages();
                foreach ($validLanguages as $validLanguage) {
                    $this->localeService->setLocale($validLanguage);

                    $childValue = $childs[0]->getLabeledValue($element);
                    if ($childValue && $childValue->value) {
                        $resultValues[]= $childValue;
                    } else {
                        $resultValues[]= null;
                    }
                }

                $this->localeService->setLocale($currentLocale);

                $result->value = $resultValues;

                return $result;
            } else {
                $value = $childs[0]->getLabeledValue($element);
            }

            return $value;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function expandLocales()
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages()
    {
        if ($this->locales) {
            $validLanguages = $this->locales;
        } else {
            $validLanguages = Tool::getValidLanguages();
        }

        return $validLanguages;
    }

    /**
     * @return mixed
     */
    public function getAsArray()
    {
        return $this->asArray;
    }

    /**
     * @param mixed $asArray
     */
    public function setAsArray($asArray)
    {
        $this->asArray = $asArray;
    }
}
