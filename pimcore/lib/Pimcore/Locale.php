<?php

namespace Pimcore;

class Locale {

    /**
     * @param $locale
     * @return bool
     */
    public static function isLocale($locale) {

        $locales = array_flip(\ResourceBundle::getLocales(null));
        $exists = isset($locales[$locale]);
        return $exists;
    }
}