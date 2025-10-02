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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Translation\TranslationEntriesDumper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class DumpTranslationEntriesListener implements EventSubscriberInterface
{
    private TranslationEntriesDumper $dumper;

    public function __construct(TranslationEntriesDumper $dumper)
    {
        $this->dumper = $dumper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->dumper->dumpToDb();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->dumper->dumpToDb();
    }
}
