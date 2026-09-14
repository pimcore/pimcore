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

namespace Pimcore\Helper;

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
     * Classes and interfaces living directly in the `Pimcore\Model\DataObject` namespace, which is
     * also where a generated DataObject class is emitted. A DataObject class named after one of
     * them shadows the core class: the application's PSR-4 prefix `Pimcore\Model\DataObject\` =>
     * `var/classes/DataObject` is longer than the core's `Pimcore\Model\` => `models`, so the
     * generated file wins and the core class is never loaded.
     *
     * Keep in sync with the contents of `models/DataObject`. `concrete` and `folder` are covered by
     * self::PIMCORE already. Exposed through self::getAllDataObjectClassReservedWords() rather than
     * as a public constant, so the list stays consumable without becoming a BC commitment.
     */
    private const PIMCORE_DATA_OBJECT_CLASSES = [
        'abstractobject', 'classdefinition', 'classdefinitioninterface', 'classificationstore',
        'definitionmodifier', 'fieldcollection', 'importdataserviceinterface',
        'lazyloadedfieldsinterface', 'listing', 'localizedfield', 'objectawarefieldinterface',
        'objectbrick', 'ownerawarefieldinterface', 'pregetvaluehookinterface',
        'selectoptionsinterface', 'service',
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

    /**
     * Deliberately not folded into getAllReservedWords(): the names in
     * self::PIMCORE_DATA_OBJECT_CLASSES only collide for a DataObject class name. Select options
     * are generated into the `Pimcore\Model\DataObject\SelectOptions` sub-namespace, so adding
     * them to the shared list would reject existing, harmless select-options configurations.
     *
     * @return string[]
     */
    public function getAllDataObjectClassReservedWords(): array
    {
        return [
            ...$this->getAllReservedWords(),
            // self:: rather than static::, unlike the public constants above: a private constant
            // cannot be overridden, so late static binding would only be misleading here.
            ...self::PIMCORE_DATA_OBJECT_CLASSES,
        ];
    }

    public function isReservedDataObjectClassName(string $name): bool
    {
        return in_array(
            strtolower($name),
            $this->getAllDataObjectClassReservedWords(),
            true
        );
    }
}
