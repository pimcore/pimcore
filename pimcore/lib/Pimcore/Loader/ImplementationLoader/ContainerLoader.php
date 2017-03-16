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

namespace Pimcore\Loader\ImplementationLoader;

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Loader\ImplementationLoader\Traits\MapLoaderTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads implementations from a name => serviceId map through the container
 */
class ContainerLoader implements LoaderInterface
{
    use MapLoaderTrait;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param array $map
     */
    public function __construct(ContainerInterface $container, array $map = [])
    {
        $this->container = $container;
        $this->map       = $map;
    }

    /**
     * @param string $name
     * @param string $serviceId
     */
    public function register($name, $serviceId)
    {
        $this->map[$name] = $serviceId;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $name) : bool
    {
        return isset($this->map[$name]);
    }

    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = [])
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        $serviceId = $this->map[$name];
        if (!$this->container->has($serviceId)) {
            throw new UnsupportedException(sprintf('Definition for "%s" does not exist', $serviceId));
        }

        return $this->container->get($serviceId);
    }
}
