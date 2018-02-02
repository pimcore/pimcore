<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation;

interface TranslationInterface
{
    /**
     * Returns a list of valid languages
     *
     * @return array
     */
    public static function getLanguages(): array;

    /**
     * Detemines if backend can handle the language
     *
     * @param $locale
     *
     * @return bool
     */
    public static function isValidLanguage($locale): bool;

    /**
     * @param $id
     *
     * @return mixed
     */
    public static function getByKey($id);
}
