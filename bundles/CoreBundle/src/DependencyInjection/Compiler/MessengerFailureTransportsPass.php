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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function array_unique;
use function array_values;
use function is_string;

/**
 * Exposes which messenger transports are failure transports as a parameter, read from the metadata
 * Symfony itself puts on every transport (`messenger.receiver` tag, `is_failure_transport`). Failure
 * transports can carry any name, so the telemetry snapshot must not guess them from a naming convention.
 *
 * @internal
 */
final class MessengerFailureTransportsPass implements CompilerPassInterface
{
    private const PARAMETER = 'pimcore.telemetry.messenger_failure_transports';

    public function process(ContainerBuilder $container): void
    {
        $names = [];

        foreach ($container->findTaggedServiceIds('messenger.receiver') as $tags) {
            foreach ($tags as $tag) {
                if (($tag['is_failure_transport'] ?? false) === true && is_string($tag['alias'] ?? null)) {
                    $names[] = $tag['alias'];
                }
            }
        }

        $container->setParameter(self::PARAMETER, array_values(array_unique($names)));
    }
}
