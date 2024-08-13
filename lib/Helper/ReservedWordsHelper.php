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

namespace Pimcore\Helper;

/**
 * Keep in sync with bundles/AdminBundle/public/js/pimcore/object/helpers/reservedWords.js
 */
class ReservedWordsHelper
{
    public const PHP_KEYWORDS = [
        'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue',
        'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach',
        'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach',
        'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof',
        'interface', 'isset', 'list', 'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public',
        'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use',
        'var', 'while', 'xor', 'yield', 'yield_from',
    ];

    public const PHP_CLASSES = [
        'self', 'static', 'parent',
    ];

    public const PHP_OTHER_WORDS = [
        'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void', 'iterable', 'object', 'mixed', 'never',
        'enum', 'resource', 'numeric',
    ];

    public const PIMCORE = [
        'data', 'folder', 'permissions', 'dao', 'concrete', 'items',
    ];

    /**
     * @return string[]
     */
    public function getAllPhpReservedWords(): array
    {
        return [
            ...static::PHP_KEYWORDS,
            ...static::PHP_CLASSES,
            ...static::PHP_OTHER_WORDS,
        ];
    }

    /**
     * @return string[]
     */
    public function getAllReservedWords(): array
    {
        return [
            ...$this->getAllPhpReservedWords(),
            ...static::PIMCORE,
        ];
    }

    public function isReservedWord(string $word): bool
    {
        return in_array(
            strtolower($word),
            $this->getAllReservedWords(),
            true
        );
    }
}
