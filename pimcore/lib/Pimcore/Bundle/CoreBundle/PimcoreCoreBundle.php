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

namespace Pimcore\Bundle\CoreBundle;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\BackwardsCompatibleAliasesPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\CacheCollectorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsParametersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\NavigationRendererPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\PhpTemplatingPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\PimcoreContextResolverAwarePass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\PimcoreGlobalTemplatingVariablesPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ServiceControllersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\SessionConfiguratorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\TemplateVarsProviderPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\TemplatingEngineAwareHelperPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WebDebugToolbarListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreCoreBundle extends Bundle
{
    public function getContainerExtension()
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
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BackwardsCompatibleAliasesPass());
        $container->addCompilerPass(new PimcoreContextResolverAwarePass());
        $container->addCompilerPass(new PhpTemplatingPass());
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new NavigationRendererPass());
        $container->addCompilerPass(new CacheCollectorPass());
        $container->addCompilerPass(new PimcoreGlobalTemplatingVariablesPass());
        $container->addCompilerPass(new TemplatingEngineAwareHelperPass());
        $container->addCompilerPass(new TemplateVarsProviderPass());
        $container->addCompilerPass(new ServiceControllersPass());
        $container->addCompilerPass(new SessionConfiguratorPass());
        $container->addCompilerPass(new WebDebugToolbarListenerPass());
        $container->addCompilerPass(new DoctrineMigrationsParametersPass());
    }
}
