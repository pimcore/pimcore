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

namespace Pimcore\Model\Translation;

/**
 * @deprecated Will be removed in Pimcore 10, as there's only one Translation class left
 */
interface TranslationInterface
{
    /**
     * Returns a list of valid languages
     *
     * @return array
     *
     * @deprecated use getValidLanguages with domain param
     */
    public static function getLanguages(): array;

    /**
     * Detemines if backend can handle the language
     *
     * @param string $locale
     *
     * @return bool
     *
     * @deprecated use IsAValidLanguage with domain & $locale params
     */
    public static function isValidLanguage($locale): bool;

    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getByKey($id);
}
