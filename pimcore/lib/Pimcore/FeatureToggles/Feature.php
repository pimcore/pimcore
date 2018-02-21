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

use MyCLabs\Enum\Enum;

/**
 * @method static Feature NONE()
 * @method static Feature ALL()
 */
abstract class Feature extends Enum
{
    const NONE = 0;

    /**
     * Type is used to register different feature types on the feature manager
     *
     * @return string
     */
    abstract public static function getType(): string;

    public static function fromName(string $name): self
    {
        return static::__callStatic($name, []);
    }

    public static function toArray()
    {
        $class = get_called_class();
        if (!array_key_exists($class, static::$cache)) {
            static::$cache[$class] = static::buildConstants($class);
        }

        return static::$cache[$class];
    }

    private static function buildConstants(string $class): array
    {
        $reflection = new \ReflectionClass($class);
        $constants  = $reflection->getConstants();

        // add a magic ALL constant
        $allMask = 0;
        $count   = 0;
        $defined = [];

        foreach ($constants as $name => $mask) {
            // check if 0 is used for another constant
            if (0 === $mask) {
                if ('NONE' !== $name) {
                    throw new \LogicException(sprintf(
                        'The constant %s tries to re-define the bit mask 0 which is reserved for the NONE value.',
                        $name
                    ));
                }

                continue;
            }

            // check if none is overwritten
            if ('NONE' === $name && 0 !== $mask) {
                throw new \LogicException(sprintf(
                    'The constant NONE is overwritten with value %d, but NONE needs to be 0.',
                    $mask
                ));
            }

            // check if the mask is a power of 2
            if (($mask & ($mask - 1)) !== 0) {
                throw new \LogicException(sprintf(
                    'The mask %d for constant %s is not a power of 2.',
                    $mask,
                    $name
                ));
            }

            // check for duplicate values
            if (isset($defined[$mask])) {
                throw new \LogicException(sprintf(
                    'The bit value %d for constant %s is already defined by %s. Please use distinct values for every feature.',
                    $mask,
                    $name,
                    $defined[$mask]
                ));
            }

            // limit flags to 31 as ALL would exceed PHP_INT_MAX on 32-bit systems with more flags
            if (++$count > 31) {
                throw new \LogicException('A feature can have a maximum of 31 flags excluding NONE and ALL.');
            }

            $defined[$mask] = $name;

            $allMask |= $mask;
        }

        $constants['ALL'] = $allMask;
        asort($constants);

        return $constants;
    }

    public function getValue(): int
    {
        return parent::getValue();
    }
}
