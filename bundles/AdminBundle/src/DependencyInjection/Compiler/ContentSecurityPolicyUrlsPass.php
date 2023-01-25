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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class ContentSecurityPolicyUrlsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ContentSecurityPolicyHandler::class);

        $config = $container->getParameter('pimcore_admin.config');

        foreach ($config['admin_csp_header']['additional_urls'] as $additionalUrlsKey => $additionalUrlsArr) {
            $definition->addMethodCall('addAllowedUrls', [$additionalUrlsKey, $additionalUrlsArr]);
        }
    }
}
