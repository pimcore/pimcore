<?php
declare(strict_types=1);

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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;

/**
 * @internal
 */
final class LFExpander extends AbstractOperator
{
    private \stdClass|LocaleServiceInterface $localeService;

    /**
     * @var string[]
     */
    private array $locales;

    private bool $asArray;

    public function __construct(LocaleServiceInterface $localeService, \stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->localeService = $localeService;

        $this->locales = $config->locales ?? [];
        $this->asArray = $config->asArray ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $children = $this->getChildren();
        if (isset($children[0])) {
            if ($this->getAsArray()) {
                $result = new ResultContainer();
                $result->label = $this->label;
                $resultValues = [];

                $currentLocale = $this->localeService->getLocale();

                $validLanguages = $this->getValidLanguages();
                foreach ($validLanguages as $validLanguage) {
                    $this->localeService->setLocale($validLanguage);

                    $childValue = $children[0]->getLabeledValue($element);
                    if ($childValue && $childValue->value) {
                        $resultValues[] = $childValue;
                    } else {
                        $resultValues[] = null;
                    }
                }

                $this->localeService->setLocale($currentLocale);

                $result->value = $resultValues;

                return $result;
            } else {
                $value = $children[0]->getLabeledValue($element);
            }

            return $value;
        }

        return null;
    }

    public function expandLocales(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages(): array
    {
        if ($this->locales) {
            $validLanguages = $this->locales;
        } else {
            $validLanguages = Tool::getValidLanguages();
        }

        return $validLanguages;
    }

    public function getAsArray(): bool
    {
        return $this->asArray;
    }

    public function setAsArray(bool $asArray): void
    {
        $this->asArray = $asArray;
    }
}
