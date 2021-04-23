<?php

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Country;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;

class CountryOptionsProvider implements SelectOptionsProviderInterface
{
    /** @var LocaleServiceInterface */
    private $localeService;

    public function __construct(LocaleServiceInterface $localeService)
    {
        $this->localeService = $localeService;
    }

    public function getOptions($context, $fieldDefinition)
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

    public function hasStaticOptions($context, $fieldDefinition)
    {
        return true;
    }

    public function getDefaultValue($context, $fieldDefinition)
    {
        if ($fieldDefinition instanceof Country) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
