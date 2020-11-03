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

use Pimcore\Templating\Helper\TemplatingEngineAwareHelperInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds a call to set the PHP templating engine to all helpers implementing TemplatingEngineAwareHelperInterface
 *
 * @deprecated
 */
class TemplatingEngineAwareHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('templating.helper') as $id => $tags) {
            $definition = $container->getDefinition($id);

            $class = $definition->getClass();
            if (!$class) {
                continue;
            }

            // if class is configured as parameter, try to resolve class name from parameter
            if (preg_match('/^%([^%]+)%$/', $class, $matches)) {
                if ($container->hasParameter($matches[1])) {
                    $class = $container->getParameter($matches[1]);
                } else {
                    continue;
                }
            }

            if (!class_exists($class)) {
                continue;
            }

            $reflector = new \ReflectionClass($class);
            if ($reflector->implementsInterface(TemplatingEngineAwareHelperInterface::class)) {
                $definition->addMethodCall('setTemplatingEngine', [new Reference('pimcore.templating.engine.php')]);
            }
        }
    }
}
