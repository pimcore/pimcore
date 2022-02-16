<?php

namespace Pimcore\Translation;

use Pimcore\Model\Translation;

class TranslationEntriesDumper
{
    /**
     * @var Translation[]
     */
    private static $translations = [];

    /**
     * @param Translation $translation
     */
    public static function addToSaveQueue(Translation $translation)
    {
        self::$translations[$translation->getKey()] = $translation;
    }

    public function dumpToDb()
    {
        foreach (self::$translations as $translation) {
            $translation->save();
        }
        self::$translations = [];
    }
}
