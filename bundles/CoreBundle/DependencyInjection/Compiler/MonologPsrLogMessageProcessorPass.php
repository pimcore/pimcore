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
    public function process(ContainerBuilder $container)
    {
        $processorId = 'monolog.processor.psr_log_message';

        if ($container->has($processorId)) {
            return;
        }

        $processor = new Definition(PsrLogMessageProcessor::class);
        $processor->setPublic(false);

        $container->setDefinition($processorId, $processor);
    }
}
