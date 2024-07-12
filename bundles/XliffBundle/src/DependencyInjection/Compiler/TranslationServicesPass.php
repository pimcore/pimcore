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

namespace Pimcore\Bundle\XliffBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\XliffBundle\ExportDataExtractorService\ExportDataExtractorServiceInterface;
use Pimcore\Bundle\XliffBundle\ImporterService\ImporterServiceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class TranslationServicesPass implements CompilerPassInterface
{
    /**
     * Registers each service with tag pimcore.translation.data-extractor as data extractor for the translations export data extractor service.
     * Registers each service with tag pimcore.translation.importer as importer for the translations importer service.
     *
     */
    public function process(ContainerBuilder $container): void
    {
        $providers = $container->findTaggedServiceIds('pimcore.translation.data-extractor');

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['type'])) {
                    throw new \Exception('service with tag "pimcore.translation.data-extractor" but without type registered');
                }
                $definition = $container->getDefinition(ExportDataExtractorServiceInterface::class);
                $definition->addMethodCall('registerDataExtractor', [$attributes['type'], new Reference($id)]);
            }
        }

        $providers = $container->findTaggedServiceIds('pimcore.translation.importer');

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['type'])) {
                    throw new \Exception('service with tag "pimcore.translation.data-extractor" but without type registered');
                }
                $definition = $container->getDefinition(ImporterServiceInterface::class);
                $definition->addMethodCall('registerImporter', [$attributes['type'], new Reference($id)]);
            }
        }
    }
}
