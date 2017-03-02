<?php

namespace Pimcore\Bundle\PimcoreBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class Locale {

    /**
     * @var null|RequestStack
     */
    protected $requestStack;

    /**
     * Locale constructor.
     * @param RequestStack|null $requestStack
     */
    public function __construct(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param $locale
     * @return bool
     */
    public function isLocale($locale) {

        $locales = array_flip($this->getLocaleList());
        $exists = isset($locales[$locale]);
        return $exists;
    }

    /**
     * @return string
     */
    public function findLocale() {
        if ($this->requestStack) {
            $masterRequest = $this->requestStack->getMasterRequest();

            if ($masterRequest) {
                return $masterRequest->getLocale();
            }
        }

        $defaultLocale = \Pimcore\Tool::getDefaultLanguage();
        if($defaultLocale) {
            return $defaultLocale;
        }

        return "";
    }

    /**
     * @return array
     */
    public function getLocaleList() {
        $locales = \ResourceBundle::getLocales(null);
        return $locales;
    }

    /**
     * @param null $locale
     * @return array
     */
    public function getDisplayRegions($locale = null) {

        if(!$locale) {
            $locale = $this->findLocale();
        }

        $regions = [];
        $locales = $this->getLocaleList();
        foreach($locales as $code) {
            $regionCode = \Locale::getRegion($code);
            if($regionCode) {
                $regionTranslation = \Locale::getDisplayRegion($code, $locale);
                $regions[$regionCode] = $regionTranslation;
            }
        }

        return $regions;
    }
}
