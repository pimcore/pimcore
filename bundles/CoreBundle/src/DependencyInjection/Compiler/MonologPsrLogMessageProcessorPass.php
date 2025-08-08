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

use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * The application logger has a dependency on the monolog.processor.psr_log_message
 * service which is only registered conditionally by the monolog bundle (depending on
 * if a handler using the PSR log message processor is registered). As the application
 * logger fails if the processor service is missing, we register it conditionally here.
 */
class MonologPsrLogMessageProcessorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processorId = 'monolog.processor.psr_log_message';

        // We need to ignore this due to this bug: https://github.com/phpstan/phpstan-symfony/issues/15
        // @phpstan-ignore-next-line
        if (! $container->has($processorId)) {
            $processor = new Definition(PsrLogMessageProcessor::class);
            $processor->setPublic(false);

            $container->setDefinition($processorId, $processor);
        }
    }
}
