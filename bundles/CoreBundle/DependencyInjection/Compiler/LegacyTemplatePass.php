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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\CoreBundle\EventListener\LegacyTemplateListener;
use Pimcore\Bundle\CoreBundle\Templating\LegacyTemplateGuesser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @deprecated
 */
class LegacyTemplatePass implements CompilerPassInterface
{
    /**
     * Replace SensioFrameworkExtraBundle template guesser & view listener with our implementation to support PHP templates
     *
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sensio_framework_extra.view.guesser')) {
            $definition = $container->getDefinition('sensio_framework_extra.view.guesser');
            $definition
                ->setPublic(true)
                ->setClass(LegacyTemplateGuesser::class)
                ->addArgument(new Reference('templating'));
        }

        if ($container->hasDefinition('sensio_framework_extra.view.listener')) {
            $definition = $container->getDefinition('sensio_framework_extra.view.listener');
            $definition
                ->setClass(LegacyTemplateListener::class)
                ->addMethodCall('setTemplateEngine', [
                    new Reference('templating'),
                ]);
        }
    }
}
