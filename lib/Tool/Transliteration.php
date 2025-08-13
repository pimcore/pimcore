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

namespace Pimcore\Tool;

/**
 * @internal
 */
class Transliteration
{
    public static function toASCII(string $value, ?string $language = null): string
    {
        if ($language !== null && in_array($language.'-ASCII', transliterator_list_ids())) {
            return transliterator_transliterate($language.'-ASCII; [^\u001F-\u007f] remove', $value);
        }

        return transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove', $value);
    }
}
