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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Config\Processor;

/**
 * @internal
 */
class PlaceholderProcessor
{
    /**
     * Merges placeholders recursively into an an array structure. Replaces placeholders
     * in both keys and values.
     */
    public function mergePlaceholders(array $config, array $placeholders): array
    {
        return $this->processArrayValue($config, $placeholders);
    }

    private function processValue(mixed $value, array $placeholders): mixed
    {
        if (is_string($value)) {
            $value = strtr($value, $placeholders);
        } elseif (is_array($value)) {
            $value = $this->processArrayValue($value, $placeholders);
        }

        return $value;
    }

    private function processArrayValue(array $value, array $placeholders): array
    {
        if (empty($placeholders) || empty($value)) {
            return $value;
        }

        $merged = [];
        foreach ($value as $key => $val) {
            $key = $this->processValue($key, $placeholders);
            $val = $this->processValue($val, $placeholders);

            $merged[$key] = $val;
        }

        return $merged;
    }
}
