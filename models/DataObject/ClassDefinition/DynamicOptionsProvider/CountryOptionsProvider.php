<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Country;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;

class CountryOptionsProvider implements SelectOptionsProviderInterface
{
    private LocaleServiceInterface $localeService;

    public function __construct(LocaleServiceInterface $localeService)
    {
        $this->localeService = $localeService;
    }

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $countries = $this->localeService->getDisplayRegions();
        asort($countries);
        $options = [];
        $restrictTo = null;

        if ($fieldDefinition instanceof Country || $fieldDefinition instanceof Countrymultiselect) {
            $restrictTo = $fieldDefinition->getRestrictTo();
            if ($restrictTo) {
                $restrictTo = explode(',', $restrictTo);
            }
        }

        foreach ($countries as $short => $translation) {
            if (strlen($short) === 2) {
                if ($restrictTo && !in_array($short, $restrictTo)) {
                    continue;
                }
                $options[] = [
                    'key' => $translation,
                    'value' => $short,
                ];
            }
        }

        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        if ($fieldDefinition instanceof Country) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
