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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceControllersPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        $availableServiceControllers = [];

        foreach ($container->getServiceIds() as $id) {
            if (!$container->hasDefinition($id))
                continue;

            $service = $container->getDefinition($id);
            $class = $service->getClass();

            if (!$class) continue;
            if (!class_exists($class)) continue;
            if (!in_array(Controller::class, class_parents($class), true)) continue;

            $availableServiceControllers[] = $id;
        }

        $container->setParameter("pimcore_admin.service_controllers", $availableServiceControllers);
    }
}
