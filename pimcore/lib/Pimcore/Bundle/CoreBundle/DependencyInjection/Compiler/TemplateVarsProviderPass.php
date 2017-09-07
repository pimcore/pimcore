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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Service\Request\TemplateVarsResolver;
use Pimcore\Templating\Vars\TemplateVarsProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class TemplateVarsProviderPass implements CompilerPassInterface
{
    /**
     * Hooks template vars providers tagged with "pimcore.templating.vars_provider" into the template vars resolver.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition     = $container->getDefinition(TemplateVarsResolver::class);
        $taggedServices = $container->findTaggedServiceIds('pimcore.templating.vars_provider');

        foreach ($taggedServices as $id => $tags) {
            $providerDefinition = $container->getDefinition($id);

            $reflector = new \ReflectionClass($providerDefinition->getClass());
            if (!$reflector->implementsInterface(TemplateVarsProviderInterface::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Template vars provider %s is expected to implement %s',
                    $id,
                    TemplateVarsProviderInterface::class
                ));
            }

            $definition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
