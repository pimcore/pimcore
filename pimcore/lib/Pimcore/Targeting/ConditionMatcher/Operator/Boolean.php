<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\ConditionMatcher\Operator;

class Boolean implements OperatorInterface
{
    const AND = 'and';
    const OR = 'or';
    const AND_NOT = 'and_not';

    /**
     * @var array
     */
    private static $validTypes = [
        self:: AND,
        self:: OR,
        self::AND_NOT
    ];

    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid boolean operator "%s"', $type));
        }

        $this->type = $type;
    }

    public static function fromString(string $type): self
    {
        return new self($type);
    }

    public function operate(bool $a, bool $b): bool
    {
        switch ($this->type) {
            case self:: AND:
                return $a && $b;
                break;

            case self:: OR:
                return $a || $b;
                break;

            case self::AND_NOT:
                return $a && !$b;
                break;
        }
    }
}
