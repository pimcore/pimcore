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

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Extension\Config;
use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;

class AreabrickManager implements AreabrickManagerInterface
{
    /**
     * @var AreabrickInterface[]
     */
    protected $bricks = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function register(AreabrickInterface $brick)
    {
        if (array_key_exists($brick->getId(), $this->bricks)) {
            throw new ConfigurationException(sprintf('Areabrick %s is already registered', $brick->getId()));
        }

        $this->bricks[$brick->getId()] = $brick;
    }

    /**
     * @inheritdoc
     */
    public function getBrick($id)
    {
        $id = $this->getBrickIdentifier($id);

        if (!isset($this->bricks[$id])) {
            throw new ConfigurationException(sprintf('Areabrick %s is not registered', $id));
        }

        return $this->bricks[$id];
    }

    /**
     * @inheritdoc
     */
    public function getBricks()
    {
        return $this->bricks;
    }

    /**
     * @inheritdoc
     */
    public function enable($brick)
    {
        $this->setState($brick, true);
    }

    /**
     * @inheritdoc
     */
    public function disable($brick)
    {
        $this->setState($brick, false);
    }

    /**
     * Enables/disables an areabrick
     *
     * @param string $id
     * @param bool $state
     */
    public function setState($id, $state)
    {
        $brick = $this->getBrick($id);
        $config = $this->getBrickConfig();

        // set true/false state here as it will be filtered out by setBrickConfig
        $config[$brick->getId()] = (bool)$state;

        $this->setBrickConfig($config);
    }

    /**
     * Determines if an areabrick is enabled. Bricks are enabled by default an can be switched off by setting
     * the state explicitely to false in the extension config.
     *
     * @param string|AreabrickInterface $brick
     *
     * @return bool
     */
    public function isEnabled($brick)
    {
        $id     = $this->getBrickIdentifier($brick);
        $config = $this->getBrickConfig();

        $enabled = true;
        if (isset($config[$id]) && $config[$id] === false) {
            $enabled = false;
        }

        return $enabled;
    }

    /**
     * @return array
     */
    private function getBrickConfig()
    {
        $config = $this->config->loadConfig();
        if (isset($config->areabricks)) {
            return $config->areabricks->toArray();
        }

        return [];
    }

    /**
     * @param array $config
     */
    private function setBrickConfig(array $config)
    {
        $filtered = [];
        foreach ($config as $id => $state) {
            // only write disabled bricks to config
            // bricks without a config state are automatically enabled
            if (!$state) {
                $filtered[$id] = false;
            }
        }

        $config = $this->config->loadConfig();
        $config->areabricks = $filtered;

        $this->config->saveConfig($config);
    }

    /**
     * @param string|AreabrickInterface $brick
     *
     * @return string
     */
    protected function getBrickIdentifier($brick)
    {
        $identifier = $brick;
        if ($brick instanceof AreabrickInterface) {
            $identifier = $brick->getId();
        }

        return $identifier;
    }
}
