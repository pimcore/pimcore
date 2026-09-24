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

namespace Pimcore\Bundle\CoreBundle;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\CacheFallbackPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ContainerAwarePass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineCommandPrefixPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\HtmlSanitizerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ImageAdapterAliasPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\LongRunningHelperPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MessageBusPublicPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPsrLogMessageProcessorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPublicLoggerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\NavigationRendererPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ProfilerAliasPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterImageOptimizersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterMaintenanceTaskPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RoutingLoaderPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\SerializerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ServiceControllersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\TranslationSanitizerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
class PimcoreCoreBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreCoreExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ContainerAwarePass());
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new DoctrineCommandPrefixPass());
        $container->addCompilerPass(new NavigationRendererPass());
        $container->addCompilerPass(new ServiceControllersPass());
        $container->addCompilerPass(new MonologPublicLoggerPass());
        $container->addCompilerPass(new MonologPsrLogMessageProcessorPass());
        $container->addCompilerPass(new LongRunningHelperPass());
        $container->addCompilerPass(new WorkflowPass());
        $container->addCompilerPass(new RegisterImageOptimizersPass());
        $container->addCompilerPass(new RegisterMaintenanceTaskPass());
        $container->addCompilerPass(new RoutingLoaderPass());
        $container->addCompilerPass(new ProfilerAliasPass());
        $container->addCompilerPass(new CacheFallbackPass());
        $container->addCompilerPass(new MessageBusPublicPass());
        $container->addCompilerPass(new HtmlSanitizerPass());
        $container->addCompilerPass(new TranslationSanitizerPass());
        $container->addCompilerPass(new SerializerPass());
        $container->addCompilerPass(new ImageAdapterAliasPass());
    }

    public function boot(): void
    {
        if (AttributeSanitizer::isConfigured()) {
            // an application bundle already installed an explicit policy (e.g. via its own
            // boot() calling AttributeSanitizer::setInstance()). Application bundles register at
            // the default priority (0, see BundleCollection::addBundle()) while this bundle
            // registers at -10 (see Kernel::registerCoreBundlesToCollection()), and bundles boot
            // in descending-priority order (BundleCollection::getItems()), so application bundles
            // have already booted by the time this runs - respect their choice instead of
            // overwriting it with the config-driven default below.
            return;
        }

        if (!$this->container->getParameter('pimcore.documents.editables.link_sanitizer.strict')) {
            AttributeSanitizer::setInstance(null);

            return;
        }

        AttributeSanitizer::setInstance(new AttributeSanitizer(
            blockedUrlSchemes: $this->container->getParameter('pimcore.documents.editables.link_sanitizer.blocked_url_schemes'),
            blockUnsafeDataUrls: $this->container->getParameter('pimcore.documents.editables.link_sanitizer.block_unsafe_data_urls'),
            blockEditorSuppliedEventHandlerAttributes: true,
            requireConventionalAttributeNameShape: true,
        ));
    }

    public function shutdown(): void
    {
        // Pimcore's own test suite boots multiple kernels/containers within the same PHP process
        // (see lib/Kernel.php's shutdown-function comment). Without this, a policy installed by
        // this bundle's boot() (or by an application bundle) for one kernel would leak into the
        // next kernel's boot() and be mistaken there for an already-configured application policy,
        // silently bypassing that kernel's own "strict" config. Reset on shutdown so every kernel
        // lifecycle starts from a clean slate.
        AttributeSanitizer::setInstance(null);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
