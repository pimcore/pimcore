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

namespace Pimcore\Bundle\GoogleMarketingBundle\Config;

class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $reportConfig
     */
    public static function fromReportConfig(array $reportConfig): self
    {
        return new self($reportConfig['analytics'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function isSiteConfigured(string $configKey): bool
    {
        $config = $this->getConfigForSite($configKey);

        if (null === $config) {
            return false;
        }

        $trackId = $this->normalizeStringValue($config['trackid']);
        if (null === $trackId) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfigForSite(string $configKey): ?array
    {
        if (!isset($this->config['sites']) || !isset($this->config['sites'][$configKey])) {
            return null;
        }

        return $this->config['sites'][$configKey];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguredSites(): array
    {
        $sites = $this->config['sites'];
        if (is_array($sites)) {
            return $sites;
        }

        return [];
    }

    public function isReportingConfigured(string $configKey): bool
    {
        $config = $this->getConfigForSite($configKey);

        if (null === $config) {
            return false;
        }

        $profile = $this->normalizeStringValue($config['profile']);
        if (null === $profile) {
            return false;
        }

        return true;
    }

    private function normalizeStringValue(mixed $value): ?string
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
