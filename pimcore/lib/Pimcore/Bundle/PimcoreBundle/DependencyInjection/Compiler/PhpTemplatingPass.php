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

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Pimcore\Bundle\PimcoreBundle\Templating\TimedPhpEngine;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Bundle\FrameworkBundle\Templating\TimedPhpEngine as BaseTimedPhpEngine;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PhpTemplatingPass implements CompilerPassInterface
{
    /**
     * Replace PHP and Timed PHP engine with our implementations and register helper brokers
     *
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('templating.engine.php')) {
            $engine = $container->getDefinition('templating.engine.php');

            if ($engine->getClass() === BasePhpEngine::class) {
                $engine->setClass(PhpEngine::class);
            } else if ($engine->getClass() === BaseTimedPhpEngine::class) {
                $engine->setClass(TimedPhpEngine::class);
            }

            // add tagged helper brokers
            $helperBrokers = $this->findHelperBrokers($container);
            foreach ($helperBrokers as $helperBroker) {
                $engine->addMethodCall('addHelperBroker', [new Reference($helperBroker)]);
            }
        }
    }

    /**
     * Find registered brokers by tag
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function findHelperBrokers(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('pimcore.templating.helper_broker');

        $list = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = isset($attributes['priority']) ? (int)$attributes['priority'] : 0;
                $list[$priority][] = $id;
            }
        }

        ksort($list);

        $brokers = [];
        foreach ($list as $priority => $ids) {
            foreach ($ids as $id) {
                $brokers[] = $id;
            }
        }

        $brokers = array_unique($brokers);
        $brokers = array_reverse($brokers); // highest priority first

        return $brokers;
    }
}
