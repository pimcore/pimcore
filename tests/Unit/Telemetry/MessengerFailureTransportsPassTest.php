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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MessengerFailureTransportsPass;
use Pimcore\Tests\Support\Test\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MessengerFailureTransportsPassTest extends TestCase
{
    private const PARAMETER = 'pimcore.telemetry.messenger_failure_transports';

    /**
     * Failure transports are whatever Symfony marked as such, whatever they are called.
     */
    public function testCollectsTheFailureTransportsFromTheTagMetadata(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('messenger.transport.pimcore_core', $this->transport('pimcore_core', false));
        $container->setDefinition('messenger.transport.dead_letters', $this->transport('dead_letters', true));
        $container->setDefinition(
            'messenger.transport.pimcore_cdn_purge_failed',
            $this->transport('pimcore_cdn_purge_failed', true),
        );
        // a transport that merely sounds like one is not a failure transport
        $container->setDefinition('messenger.transport.acme_failed', $this->transport('acme_failed', false));

        (new MessengerFailureTransportsPass())->process($container);

        $this->assertSame(['dead_letters', 'pimcore_cdn_purge_failed'], $container->getParameter(self::PARAMETER));
    }

    public function testWithoutTransportsTheParameterIsAnEmptyList(): void
    {
        $container = new ContainerBuilder();

        (new MessengerFailureTransportsPass())->process($container);

        $this->assertSame([], $container->getParameter(self::PARAMETER));
    }

    private function transport(string $alias, bool $failure): Definition
    {
        return (new Definition(stdClass::class))
            ->addTag('messenger.receiver', ['alias' => $alias, 'is_failure_transport' => $failure]);
    }
}
