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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use Pimcore\DependencyInjection\CollectionServiceLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GDPRDataProviderPass implements CompilerPassInterface
{
    /**
     * Registers each service with tag pimcore.gdpr.data-provider as dataprovider for gdpr data extractor
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds('pimcore.gdpr.data-provider');

        $mapping = [];
        foreach ($providers as $id => $tags) {
            $mapping[$id] = new Reference($id);
        }

        $collectionLocator = new Definition(CollectionServiceLocator::class, [$mapping]);
        $collectionLocator->setPublic(false);
        $collectionLocator->addTag('container.service_locator');

        $manager = $container->getDefinition(Manager::class);
        $manager->setArgument('$services', $collectionLocator);
    }
}
