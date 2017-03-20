<?php
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

namespace Pimcore\ExtensionManager;

use Pimcore\Config as PimcoreConfig;
use Pimcore\File;

class Config
{
    /**
     * @var PimcoreConfig\Config
     */
    private $config;

    /**
     * @return PimcoreConfig\Config
     */
    public function loadConfig()
    {
        if (!$this->config) {
            try {
                $file = $this->locateConfigFile();
                if (file_exists($file)) {
                    $this->config = new PimcoreConfig\Config(include $file, true);
                }
            } catch (\Exception $e) {
                $this->config = new PimcoreConfig\Config([], true);
            }
        }

        return $this->config;
    }

    /**
     * @param PimcoreConfig\Config $config
     */
    public function saveConfig(PimcoreConfig\Config $config)
    {
        $this->config = $config;

        File::putPhpFile(
            $this->locateConfigFile(),
            to_php_data_file_format($config->toArray())
        );
    }

    /**
     * @return string|null
     */
    private function locateConfigFile()
    {
        return PimcoreConfig::locateConfigFile('extensions.php');
    }
}

