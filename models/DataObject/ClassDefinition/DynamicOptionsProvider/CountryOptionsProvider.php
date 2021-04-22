<?php

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Localization\LocaleServiceInterface;

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

        foreach ($countries as $short => $translation) {
            if (strlen($short) === 2) {
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
        return null;
    }
}
