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

namespace Pimcore\Extension\Bundle\Config;

use Pimcore\Config as PimcoreConfig;
use Pimcore\Extension\Config;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StateConfig
{
    /**
     * @var OptionsResolver
     */
    private static $optionsResolver;

    /**
     * @var array
     */
    private static $optionDefaults = [
        'enabled'      => false,
        'priority'     => 0,
        'environments' => []
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Lists enabled bundles from config
     *
     * @return array
     */
    public function getEnabledBundles(): array
    {
        $result  = [];
        $bundles = $this->getBundlesFromConfig();

        foreach ($bundles as $bundleName => $options) {
            if ($options['enabled']) {
                $result[$bundleName] = $options;
            }
        }

        return $result;
    }

    /**
     * Lists enabled bundle names from config
     *
     * @return array
     */
    public function getEnabledBundleNames(): array
    {
        return array_keys($this->getEnabledBundles());
    }

    /**
     * Loads bundles which are defined in configuration
     *
     * @return array
     */
    private function getBundlesFromConfig(): array
    {
        $config = $this->config->loadConfig();
        if (!isset($config->bundle)) {
            return [];
        }

        $bundles = $config->bundle->toArray();

        $result = [];
        foreach ($bundles as $bundleName => $options) {
            $result[$bundleName] = $this->normalizeOptions($options);
        }

        return $result;
    }

    /**
     * Returns the normalized bundle state from the extension manager config
     *
     * @param string $bundle
     *
     * @return array
     */
    public function getState(string $bundle): array
    {
        $bundles = $this->getBundlesFromConfig();
        if (isset($bundles[$bundle])) {
            return $bundles[$bundle];
        }

        return $this->normalizeOptions([]);
    }

    /**
     * Sets the normalized bundle state on the extension manager config
     *
     * @param string $bundle
     * @param array $options
     */
    public function setState(string $bundle, array $options)
    {
        $config = $this->config->loadConfig();

        $this->updateBundleState($config, $bundle, $options);

        $this->config->saveConfig($config);
    }

    /**
     * Batch update bundle states
     *
     * @param array $states
     */
    public function setStates(array $states)
    {
        $config = $this->config->loadConfig();

        foreach ($states as $bundle => $options) {
            $this->updateBundleState($config, $bundle, $options);
        }

        $this->config->saveConfig($config);
    }

    private function updateBundleState(PimcoreConfig\Config $config, string $bundle, array $options)
    {
        if (!isset($config->bundle)) {
            $config->bundle = new PimcoreConfig\Config([], true);
        }

        $state = [];
        if (isset($config->bundle->$bundle)) {
            $currentState = $config->bundle->$bundle;
            if ($currentState instanceof PimcoreConfig\Config) {
                $currentState = $currentState->toArray();
            }

            $state = $this->normalizeOptions($currentState);
        }

        $state = array_merge($state, $options);
        $state = $this->prepareWriteOptions($state);

        $config->bundle->$bundle = $state;
    }

    /**
     * Prepares options for writing. If all options besides enabled are the same as the default
     * value, just the state will be written as bool,
     *
     * @param array $options
     *
     * @return array|bool
     */
    private function prepareWriteOptions(array $options)
    {
        $options = $this->normalizeOptions($options);

        $isDefault = true;
        foreach (array_keys(self::$optionDefaults) as $option) {
            if ('enabled' === $option) {
                continue;
            }

            if ($options[$option] !== static::$optionDefaults[$option]) {
                $isDefault = false;
                break;
            }
        }

        if ($isDefault) {
            return $options['enabled'];
        }

        return $options;
    }

    /**
     * Normalizes options array as expected in extension manager config
     *
     * @param array|bool $options
     *
     * @return array
     */
    public function normalizeOptions($options): array
    {
        if (is_bool($options)) {
            $options = ['enabled' => $options];
        } elseif (!is_array($options)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected options as bool or as array, but got %s',
                is_object($options) ? get_class($options) : gettype($options)
            ));
        }

        $resolver = self::getOptionsResolver();
        $options  = $resolver->resolve($options);

        return $options;
    }

    private static function getOptionsResolver(): OptionsResolver
    {
        if (null !== self::$optionsResolver) {
            return self::$optionsResolver;
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(self::$optionDefaults);

        $resolver->setRequired(array_keys(self::$optionDefaults));

        $resolver->setAllowedTypes('enabled', 'bool');
        $resolver->setAllowedTypes('priority', 'int');
        $resolver->setAllowedTypes('environments', 'array');

        $resolver->setNormalizer('environments', function (Options $options, $value) {
            // normalize to string and trim
            $value = array_map(function ($item) {
                $item = (string)$item;
                $item = trim($item);

                return $item;
            }, $value);

            // remove empty values
            $value = array_filter($value, function ($item) {
                return !empty($item);
            });

            return $value;
        });

        self::$optionsResolver = $resolver;

        return self::$optionsResolver;
    }
}
