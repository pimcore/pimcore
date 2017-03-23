<?php

declare(strict_types = 1);

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

use Pimcore\Extension;
use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AreabrickManager implements AreabrickManagerInterface
{
    /**
     * @var Extension\Config
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AreabrickInterface[]
     */
    protected $bricks = [];

    /**
     * @var array
     */
    protected $brickServiceIds = [];

    /**
     * @param Extension\Config $config
     * @param ContainerInterface $container
     */
    public function __construct(Extension\Config $config, ContainerInterface $container)
    {
        $this->config    = $config;
        $this->container = $container;
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
    public function registerService(string $serviceId)
    {
        $this->brickServiceIds[] = $serviceId;
    }

    /**
     * @inheritdoc
     */
    public function getBrick($id): AreabrickInterface
    {
        $this->resolveServiceDefinitions();

        $id = $this->getBrickIdentifier($id);

        if (!isset($this->bricks[$id])) {
            throw new ConfigurationException(sprintf('Areabrick %s is not registered', $id));
        }

        return $this->bricks[$id];
    }

    /**
     * @inheritdoc
     */
    public function getBricks(): array
    {
        $this->resolveServiceDefinitions();

        return $this->bricks;
    }

    /**
     * Load services for registered brick service definitions
     */
    protected function resolveServiceDefinitions()
    {
        if (empty($this->brickServiceIds)) {
            return;
        }

        while ($serviceId = array_shift($this->brickServiceIds)) {
            if (!$this->container->has($serviceId)) {
                throw new ConfigurationException(sprintf('Definition for areabrick %s does not exist', $serviceId));
            }

            $brick = $this->container->get($serviceId);
            if (!($brick instanceof AreabrickInterface)) {
                throw new ConfigurationException(sprintf(
                    'Definition for areabrick %s does not implement AreabrickInterface (got %s)',
                    $serviceId,
                    is_object($brick) ? get_class($brick) : gettype($brick)
                ));
            }

            $this->register($brick);
        }
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
        $brick  = $this->getBrick($id);
        $config = $this->getBrickConfig();

        if ((bool)$state) {
            if (isset($config[$brick->getId()])) {
                unset($config[$brick->getId()]);
            }
        } else {
            $config[$brick->getId()] = false;
        }

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
    public function isEnabled($brick): bool
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
        if (isset($config->areabrick)) {
            return $config->areabrick->toArray();
        }

        return [];
    }

    /**
     * @param array $config
     */
    private function setBrickConfig(array $config)
    {
        $cfg            = $this->config->loadConfig();
        $cfg->areabrick = $config;

        $this->config->saveConfig($cfg);
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
