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

use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick;
use Pimcore\Image\AdapterInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function extension_loaded;

/**
 * @internal
 */
final class ImageAdapterAliasPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(AdapterInterface::class)) {
            $container->getDefinition(AdapterInterface::class)->setPublic(true)->setShared(false);
        } elseif ($container->hasAlias(AdapterInterface::class)) {
            $container->getAlias(AdapterInterface::class)->setPublic(true);
        } else {
            if (extension_loaded('imagick')) {
                $alias = new Alias(Imagick::class, true);
                $container->setAlias(AdapterInterface::class, $alias);
            } else {
                $alias = new Alias(GD::class, true);
                $container->setAlias(AdapterInterface::class, $alias);
            }
        }
    }
}
