<?php

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
