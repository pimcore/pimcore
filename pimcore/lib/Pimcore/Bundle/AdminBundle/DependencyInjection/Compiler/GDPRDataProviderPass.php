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


use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GDPRDataProviderPass implements CompilerPassInterface
{

    /**
     * Registers each service with tag pimcore.gdpr.data-provider as dataprovider for gdpr data extractor
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('pimcore.gdpr.data-provider');
        $manager = $container->getDefinition(Manager::class);

        foreach($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                if (!array_key_exists('id', $tag)) {
                    throw new \Exception(sprintf('Missing "id" attribute on data provider DI tag for service %s', $id));
                }

                // register the brick with its ID on the areabrick manager
                $manager->addMethodCall('registerService', [$tag['id'], $id]);
            }
        }
    }
}