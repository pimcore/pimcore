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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds tagged navigation renderers to navigation helper
 */
class NavigationRendererPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('pimcore.navigation.renderer');

        $map = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $alias = null;
                if (isset($tag['alias']) && !empty($tag['alias'])) {
                    $alias = (string)$tag['alias'];
                }

                if (!$alias) {
                    throw new InvalidConfigurationException(sprintf(
                        'Missing "alias" attribute on navigtion renderer tag for service "%s"',
                        $id
                    ));
                }

                $map[$alias] = new Reference($id);
            }
        }

        $locatorDefinition = $container->findDefinition('pimcore.templating.navigation.renderer_locator');
        $locatorDefinition->setArgument(0, $map);
    }
}
