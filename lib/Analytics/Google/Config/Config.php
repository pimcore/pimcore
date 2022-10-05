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

namespace Pimcore\Analytics\Google\Config;

class Config
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromReportConfig(array $reportConfig): self
    {
        $config = null;
        if ($reportConfig['analytics']) {
            $config = $reportConfig['analytics'];
        } else {
            $config = [];
        }

        return new self($config);
    }

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
     * @param string $configKey
     *
     * @return null|array
     */
    public function getConfigForSite(string $configKey)
    {
        if (!isset($this->config['sites']) || !isset($this->config['sites'][$configKey])) {
            return null;
        }

        return $this->config['sites'][$configKey];
    }

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
