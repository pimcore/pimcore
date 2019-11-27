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

namespace Pimcore\Analytics\GoogleTagManager\Config;

use Pimcore\Config\Config as ConfigObject;

class Config
{
    /**
     * @var ConfigObject
     */
    private $config;

    public function __construct(ConfigObject $config)
    {
        $this->config = $config;
    }

    public function getConfig(): ConfigObject
    {
        return $this->config;
    }

    public function isSiteConfigured(string $configKey): bool
    {
        $config = $this->getConfigForSite($configKey);

        if (null === $config) {
            return false;
        }

        $containerId = $this->normalizeStringValue($config->containerId);
        if (null === $containerId) {
            return false;
        }

        return true;
    }

    /**
     * @param string $configKey
     *
     * @return null|ConfigObject
     */
    public function getConfigForSite(string $configKey)
    {
        if (!$this->config->sites || !$this->config->sites->$configKey) {
            return null;
        }

        return $this->config->sites->$configKey;
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function normalizeStringValue($value)
    {
        if (null === $value) {
            return $value;
        }

        $value = trim((string)$value);
        if (empty($value)) {
            return null;
        }

        return $value;
    }
}
