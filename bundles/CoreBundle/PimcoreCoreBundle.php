<?php

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

namespace Pimcore\Bundle\CoreBundle;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\CacheFallbackPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\DebugStopwatchPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\LongRunningHelperPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MessageBusPublicPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPsrLogMessageProcessorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPublicLoggerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\NavigationRendererPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\PasswordFactoryDecoratorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ProfilerAliasPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterImageOptimizersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterMaintenanceTaskPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RoutingLoaderPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ServiceControllersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\TargetingOverrideHandlersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
class PimcoreCoreBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    throw new \LogicException(sprintf('Extension %s must implement Symfony\Component\DependencyInjection\Extension\ExtensionInterface.', get_class($extension)));
                }

                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new NavigationRendererPass());
        $container->addCompilerPass(new ServiceControllersPass());
        $container->addCompilerPass(new TargetingOverrideHandlersPass());
        $container->addCompilerPass(new MonologPublicLoggerPass());
        $container->addCompilerPass(new MonologPsrLogMessageProcessorPass());
        $container->addCompilerPass(new DebugStopwatchPass());
        $container->addCompilerPass(new LongRunningHelperPass());
        $container->addCompilerPass(new WorkflowPass());
        $container->addCompilerPass(new RegisterImageOptimizersPass());
        $container->addCompilerPass(new RegisterMaintenanceTaskPass());
        $container->addCompilerPass(new RoutingLoaderPass());
        $container->addCompilerPass(new ProfilerAliasPass());
        $container->addCompilerPass(new CacheFallbackPass());
        $container->addCompilerPass(new PasswordFactoryDecoratorPass());
        $container->addCompilerPass(new MessageBusPublicPass());
    }
}
