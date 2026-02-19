<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineCommandPrefixPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('console.command') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            if ($class && is_subclass_of($class, \Doctrine\Migrations\Tools\Console\Command\DoctrineCommand::class)) {
                $definition->addMethodCall('addOption', [
                    'prefix',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Optional prefix filter for version classes, eg. Pimcore\Bundle\CoreBundle\Migrations',
                ]);
            }
        }
    }
}
