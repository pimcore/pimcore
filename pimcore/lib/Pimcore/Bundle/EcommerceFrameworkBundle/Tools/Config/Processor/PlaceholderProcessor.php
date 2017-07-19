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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\Processor;

class PlaceholderProcessor
{
    /**
     * Merges placeholders recursively into an an array structure. Replaces placeholders
     * in both keys and values.
     *
     * @param array $config
     * @param array $placeholders
     *
     * @return array
     */
    public function mergePlaceholders(array $config, array $placeholders): array
    {
        return $this->processArrayValue($config, $placeholders);
    }

    private function processValue($value, array $placeholders)
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
