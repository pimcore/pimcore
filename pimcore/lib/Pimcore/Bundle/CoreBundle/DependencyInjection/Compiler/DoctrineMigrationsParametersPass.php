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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Our migration commands and installers rely on Doctrine Migrations, but don't demand to activate the migrations bundle.
 * However, as we extend the bundle commands to set up our migration commands, the bootstrapping relies on parameters
 * which are not set if the bundle is not loaded.
 *
 * This pass adds missing parameters which are needed by the bootstrap and initializes them with default values.
 */
class DoctrineMigrationsParametersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // process an empty configuration to fetch default values
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), []);

        // set values on container if not existing
        foreach ($config as $key => $value) {
            $parameter = sprintf('doctrine_migrations.%s', $key);

            if (!$container->hasParameter($parameter)) {
                $container->setParameter($parameter, $value);
            }
        }
    }
}
