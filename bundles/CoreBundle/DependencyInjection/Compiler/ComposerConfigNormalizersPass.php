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

use Pimcore\Composer\Config\ConfigMerger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ComposerConfigNormalizersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $normalizers = [];
        foreach ($container->findTaggedServiceIds('pimcore.composer.config_normalizer') as $id => $tags) {
            $normalizers[] = new Reference($id);
        }

        if (empty($normalizers)) {
            return;
        }

        $configMerger = $container->findDefinition(ConfigMerger::class);
        $configMerger->setArgument('$normalizers', $normalizers);
    }
}
