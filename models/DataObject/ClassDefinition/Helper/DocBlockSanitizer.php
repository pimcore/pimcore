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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

/**
 * Removes comment delimiters from values that are concatenated into a generated PHPDoc block.
 *
 * @internal
 */
final class DocBlockSanitizer
{
    private const COMMENT_SEQUENCES = ['/**', '*/', '//'];

    /**
     * A single str_replace() pass is not enough: dropping one sequence can splice the surrounding
     * characters into a new one. Two asterisks followed by two slashes, for instance, collapse
     * into a docblock terminator and still close the generated docblock early. Repeating until
     * the value stops changing guarantees that none of the sequences is left in the result.
     */
    public static function sanitize(?string $value): string
    {
        $value = (string)$value;

        do {
            $previous = $value;
            $value = str_replace(self::COMMENT_SEQUENCES, '', $value);
        } while ($value !== $previous);

        return $value;
    }
}
