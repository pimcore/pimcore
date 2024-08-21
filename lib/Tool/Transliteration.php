<?php
declare(strict_types=1);

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

namespace Pimcore\Tool;

/**
 * @internal
 */
class Transliteration
{
    public static function toASCII(string $value, string $language = null): string
    {
        if ($language !== null && in_array($language.'-ASCII', transliterator_list_ids())) {
            return transliterator_transliterate($language.'-ASCII; [^\u001F-\u007f] remove', $value);
        }

        return transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove', $value);
    }
}
