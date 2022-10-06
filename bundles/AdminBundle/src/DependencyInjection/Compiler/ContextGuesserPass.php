<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Pimcore\Http\Context\PimcoreContextGuesser;
use Pimcore\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class ContextGuesserPass implements CompilerPassInterface
{
    /**
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $guesser = $container->getDefinition(PimcoreContextGuesser::class);
        $adminContexts = [
            [
                'route' => false,
                'path' => '^/admin(/.*)?$',
                'host' => false,
                'methods' => []
            ],
            [
                'route' => '^pimcore_admin_',
                'path' => false,
                'host' => false,
                'methods' => []
            ]
        ];

        foreach ($adminContexts as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', ['admin', $adminContexts]);
        }
    }
}
