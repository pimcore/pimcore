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

declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;


use Symfony\Component\DependencyInjection\ContainerInterface;

class Manager
{

    /**
     * @var string[]
     */
    protected $services = [];

    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $serviceId
     * @param string $service
     */
    public function registerService(string $serviceId, string $service) {
        $this->services[$serviceId] = $service;
    }


    /**
     * Returns registered services in sorted order
     *
     * @return DataProviderInterface[]
     */
    public function getServices(): array {
        $serviceInstances = [];
        foreach($this->services as $serviceId) {
            if($this->container->has($serviceId)) {
                $serviceInstances[] = $this->container->get($serviceId);
            }
        }

        usort($serviceInstances, function(DataProviderInterface $left, DataProviderInterface $right) {
            return $left->getSortPriority() > $right->getSortPriority();
        });

        return $serviceInstances;
    }

}