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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles block state for sub requests (saves parent state and restores it after request completes)
 *
 * @internal
 */
class BlockStateListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    public function __construct(protected BlockStateStack $blockStateStack)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($request->query->get('disableBlockClearing') || $request->request->get('disableBlockClearing')) {
            return;
        }

        // main request already has a state on the stack
        if ($event->isMainRequest()) {
            return;
        }

        // this is for $this->action() in templates when they are inside a block element
        // adds a new, empty block state to the stack which is used in the sub-request
        $this->blockStateStack->push();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($request->query->get('disableBlockClearing') || $request->request->get('disableBlockClearing')) {
            return;
        }

        if ($this->blockStateStack->count() > 1) {
            // restore parent block data by removing sub-request block state
            $this->blockStateStack->pop();
        }
    }
}
