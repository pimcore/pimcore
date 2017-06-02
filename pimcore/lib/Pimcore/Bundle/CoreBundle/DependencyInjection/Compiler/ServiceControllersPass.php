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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets a pimcore.service_controllers parameter which contains all controllers registered as service
 * as an id => class mapping. Controllers are recognized if they match one of the following:
 *
 *  - are tagged with the "controller.service_arguments" DI tag
 *  - extend Symfony\Bundle\FrameworkBundle\Controller\Controller
 *  - extend Symfony\Bundle\FrameworkBundle\Controller\AbstractController
 */
class ServiceControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceControllers = [];

        // find controllers tagged with controller.service_arguments first
        foreach ($container->findTaggedServiceIds('controller.service_arguments') as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }

            $serviceControllers[$id] = $definition->getClass();
        }

        // find all services extending Controller or AbstractController
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || !$definition->getClass()) {
                continue;
            }

            $reflector = $container->getReflectionClass($definition->getClass());
            if (!$reflector) {
                continue;
            }

            if ($reflector->isSubclassOf(AbstractController::class) || $reflector->isSubclassOf(Controller::class)) {
                $serviceControllers[$id] = $definition->getClass();
            }
        }

        ksort($serviceControllers);

        $container->setParameter('pimcore.service_controllers', $serviceControllers);
    }
}
