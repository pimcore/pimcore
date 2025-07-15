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

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class CacheFallbackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pimcore.cache.pool')) {
            $alias = new Alias('pimcore.cache.adapter.doctrine_dbal_tag_aware', true);
            $container->setAlias('pimcore.cache.pool', $alias);
        }

        // set default cache.app to Pimcore default cache, if not configured differently
        $appCache = $container->findDefinition('cache.app');
        if ($appCache instanceof ChildDefinition && $appCache->getParent() === 'cache.adapter.filesystem') {
            $this->replaceCacheDefinition($appCache);

            foreach ($container->findTaggedServiceIds('cache.pool') as $id => $arguments) {
                $cacheDef = $container->findDefinition($id);
                if ($cacheDef instanceof ChildDefinition && $cacheDef->getParent() === 'cache.app') {
                    $this->replaceCacheDefinition($cacheDef);
                }
            }
        }
    }

    private function replaceCacheDefinition(ChildDefinition $cacheDef): void
    {
        // we need to reset the arguments, so that the change of the parent works properly
        $cacheDef->setArguments([]);
        $cacheDef->setParent('pimcore.cache.pool.app');
    }
}
