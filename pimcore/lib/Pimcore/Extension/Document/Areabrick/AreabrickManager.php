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
use Pimcore\Extension\Document\Areabrick\Exception\BrickNotFoundException;
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
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function register(string $id, AreabrickInterface $brick)
    {
        if (array_key_exists($id, $this->bricks)) {
            throw new ConfigurationException(sprintf(
                'Areabrick %s is already registered as %s (trying to add %s)',
                $id,
                get_class($this->bricks[$id]),
                get_class($brick)
            ));
        }

        if (array_key_exists($id, $this->brickServiceIds)) {
            throw new ConfigurationException(sprintf(
                'Areabrick %s is already registered as service %s (trying to add %s)',
                $id,
                $this->brickServiceIds[$id],
                get_class($brick)
            ));
        }

        $brick->setId($id);

        $this->bricks[$id] = $brick;
    }

    /**
     * @inheritdoc
     */
    public function registerService(string $id, string $serviceId)
    {
        if (array_key_exists($id, $this->bricks)) {
            throw new ConfigurationException(sprintf(
                'Areabrick %s is already registered as %s (trying to add service %s)',
                $id,
                get_class($this->bricks[$id]),
                $serviceId
            ));
        }

        if (array_key_exists($id, $this->brickServiceIds)) {
            throw new ConfigurationException(sprintf(
                'Areabrick %s is already registered as service %s (trying to add service %s)',
                $id,
                $this->brickServiceIds[$id],
                $serviceId
            ));
        }

        $this->brickServiceIds[$id] = $serviceId;
    }

    /**
     * @inheritdoc
     */
    public function getBrick(string $id): AreabrickInterface
    {
        $brick = null;
        if (isset($this->bricks[$id])) {
            $brick = $this->bricks[$id];
        } else {
            $brick = $this->loadServiceBrick($id);
        }

        if (null === $brick) {
            throw new BrickNotFoundException(sprintf('Areabrick %s is not registered', $id));
        }

        return $brick;
    }

    /**
     * @inheritdoc
     */
    public function getBricks(): array
    {
        if (count($this->brickServiceIds) > 0) {
            $this->loadServiceBricks();
        }

        return $this->bricks;
    }

    /**
     * @inheritdoc
     */
    public function getBrickIds(): array
    {
        $ids = array_merge(
            array_keys($this->bricks),
            array_keys($this->brickServiceIds)
        );

        return $ids;
    }

    /**
     * Loads brick from container
     *
     * @param string $id
     *
     * @return AreabrickInterface|null
     */
    protected function loadServiceBrick(string $id)
    {
        if (!isset($this->brickServiceIds[$id])) {
            return null;
        }

        $serviceId = $this->brickServiceIds[$id];
        if (!$this->container->has($serviceId)) {
            throw new ConfigurationException(sprintf(
                'Definition for areabrick %s (defined as service %s) does not exist',
                $id,
                $serviceId
            ));
        }

        $brick = $this->container->get($serviceId);
        if (!($brick instanceof AreabrickInterface)) {
            throw new ConfigurationException(sprintf(
                'Definition for areabrick %s (defined as service %s) does not implement AreabrickInterface (got %s)',
                $id,
                $serviceId,
                is_object($brick) ? get_class($brick) : gettype($brick)
            ));
        }

        $brick->setId($id);

        // move brick to map of loaded bricks
        unset($this->brickServiceIds[$id]);
        $this->bricks[$id] = $brick;

        return $brick;
    }

    /**
     * Loads all brick instances registered as service definitions
     */
    protected function loadServiceBricks()
    {
        foreach ($this->brickServiceIds as $id => $serviceId) {
            $this->loadServiceBrick($id);
        }
    }

    /**
     * @inheritdoc
     */
    public function enable(string $id)
    {
        $this->setState($id, true);
    }

    /**
     * @inheritdoc
     */
    public function disable(string $id)
    {
        $this->setState($id, false);
    }

    /**
     * Enables/disables an areabrick
     *
     * @param string $id
     * @param bool $state
     */
    public function setState(string $id, bool $state)
    {
        // load the brick to make sure it exists
        $brick = $this->getBrick($id);
        $config = $this->getBrickConfig();

        if ($state) {
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
     * @param string $id
     *
     * @return bool
     */
    public function isEnabled(string $id): bool
    {
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
        $cfg = $this->config->loadConfig();
        $cfg->areabrick = $config;

        $this->config->saveConfig($cfg);
    }
}
