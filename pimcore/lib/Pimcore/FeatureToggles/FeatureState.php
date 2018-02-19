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

namespace Pimcore\FeatureToggles;

class FeatureState implements FeatureStateInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $value;

    public function __construct(string $type, int $state)
    {
        if ($state < 0) {
            throw new \InvalidArgumentException('State must be >= 0');
        }

        $this->type  = $type;
        $this->value = $state;
    }

    public static function fromFeature(Feature $feature): self
    {
        return new self($feature::getType(), $feature->getValue());
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isEnabled(Feature $feature, FeatureContextInterface $context): bool
    {
        if ($feature::getType() !== $this->type) {
            return false;
        }

        $value = $feature->getValue();

        // if NONE was requested, return false if anything is enabled but true
        // if current value is NONE
        if (0 === $value) {
            if ($this->value > 0) {
                return false;
            } elseif (0 === $this->value) {
                return true;
            }
        }

        // 0 is a special value denoting off at any time
        if (0 === $this->value) {
            return false;
        }

        return ($value & $this->value) === $value;
    }
}
