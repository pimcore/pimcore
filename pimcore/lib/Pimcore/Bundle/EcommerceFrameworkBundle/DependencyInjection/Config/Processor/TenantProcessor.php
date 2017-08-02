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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Config\Processor;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class TenantProcessor
{
    /**
     * Merges tenant configs with an optional _defaults key which is applied
     * to every tenant and removed afterwards.
     *
     * @param array $config
     *
     * @return array
     */
    public function mergeTenantConfig(array $config): array
    {
        // check if a _defaults tenant is set and merge its config into all defined
        // tenants
        $defaults = [];
        if (isset($config['_defaults'])) {
            $defaults = $config['_defaults'];
            unset($config['_defaults']);
        }

        foreach ($config as $tenant => $tenantConfig) {
            // tenants starting with _defaults are not included in the final config
            // but can be used for yaml inheritance
            if (preg_match('/^_defaults/i', $tenant)) {
                unset($config[$tenant]);
                continue;
            }

            $config[$tenant] = $this->mergeDefaults($defaults, $tenantConfig ?? []);
        }

        return $config;
    }

    /**
     * Merges defaults with values but does not transform scalars into arrays as array_merge_recursive does
     *
     * @param array $defaults
     * @param array $values
     *
     * @return array
     */
    private function mergeDefaults(array $defaults, array $values): array
    {
        foreach ($defaults as $k => $v) {
            if (!isset($values[$k]) || (is_array($values[$k]) && empty($values[$k]))) {
                $values[$k] = $v;
            } else {
                if (!is_array($v)) {
                    // only merging arrays
                    continue;
                }

                if (!is_array($values[$k])) {
                    throw new InvalidConfigurationException(sprintf(
                        'Can\'t merge defaults key %s as defaults is an array while the value to merge is a %s',
                        $k, gettype($values[$k])
                    ));
                }

                if ($this->isArrayAssociative($v)) {
                    $values[$k] = $this->mergeDefaults($defaults[$k], $values[$k]);
                } else {
                    $values[$k] = array_merge($defaults[$k], $values[$k]);
                }
            }
        }

        return $values;
    }

    /**
     * Checks if array is associative or sequential
     *
     * @see https://stackoverflow.com/a/173479/9131
     *
     * @param array $array
     *
     * @return bool
     */
    private function isArrayAssociative(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
