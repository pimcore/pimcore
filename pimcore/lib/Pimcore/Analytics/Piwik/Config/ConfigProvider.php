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

namespace Pimcore\Analytics\Piwik\Config;

use Pimcore\Config\Config as ConfigObject;

class ConfigProvider
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigObject|null
     */
    private $configObject;

    /**
     * @param ConfigObject|null $configObject
     */
    public function __construct(ConfigObject $configObject = null)
    {
        $this->configObject = $configObject;
    }

    public function getConfig(): Config
    {
        if (null === $this->config) {
            return new Config($this->getConfigObject());
        }

        return $this->config;
    }

    private function getConfigObject(): ConfigObject
    {
        if (null === $this->configObject) {
            $this->configObject = $this->loadDefaultConfigObject();
        }

        return $this->configObject;
    }

    protected function loadDefaultConfigObject(): ConfigObject
    {
        $reportConfig = \Pimcore\Config::getReportConfig();

        $config = $reportConfig->piwik;
        if (!$config instanceof ConfigObject) {
            $config = new ConfigObject([]);
        }

        return $config;
    }
}
