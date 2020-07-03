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

namespace Pimcore\Analytics\Google\Config;

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

    public static function fromReportConfig(ConfigObject $reportConfig): self
    {
        $config = null;
        if ($reportConfig->get('analytics')) {
            $config = $reportConfig->get('analytics');
        } else {
            $config = new ConfigObject([]);
        }

        return new self($config);
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

        $trackId = $this->normalizeStringValue($config->get('trackid'));
        if (null === $trackId) {
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
        if (!$this->config->get('sites') || !$this->config->get('sites')->$configKey) {
            return null;
        }

        return $this->config->get('sites')->$configKey;
    }

    public function getConfiguredSites(): array
    {
        $sites = $this->config->get('sites');
        if ($sites && $sites instanceof ConfigObject) {
            return array_keys($sites->toArray());
        }

        return [];
    }

    public function isReportingConfigured(string $configKey): bool
    {
        $config = $this->getConfigForSite($configKey);

        if (null === $config) {
            return false;
        }

        $profile = $this->normalizeStringValue($config->get('profile'));
        if (null === $profile) {
            return false;
        }

        return true;
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
