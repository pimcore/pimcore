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

namespace Pimcore\Translation;

use Pimcore\Model\Translation;

class TranslationEntriesDumper
{
    /**
     * @var Translation[]
     */
    private static array $translations = [];

    public static function addToSaveQueue(Translation $translation): void
    {
        self::$translations[$translation->getKey()] = $translation;
    }

    public function dumpToDb(): void
    {
        foreach (self::$translations as $translation) {
            $translation->save();
        }
        self::$translations = [];
    }
}
