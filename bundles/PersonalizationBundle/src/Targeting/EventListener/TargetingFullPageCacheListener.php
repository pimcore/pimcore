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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Bundle\PersonalizationBundle\Targeting\VisitorInfoStorageInterface;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TargetingFullPageCacheListener
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    public function __construct (
        private VisitorInfoStorageInterface $visitorInfoStorage,
        private FullPageCacheListener $fullPageCacheListener
    )
    {
    }

    public function onKernelResponse (ResponseEvent $event, ): void
    {
        if (!$event->isMainRequest ()) {
            return;
        }

        $request = $event->getRequest ();
        if (!\Pimcore\Tool::isFrontend () || \Pimcore\Tool::isFrontendRequestByAdmin ($request)) {
            return;
        }

        if (!$this->matchesPimcoreContext ($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->matchesStaticPageContext ($request)) {
            $this->fullPageCacheListener->disable ('Response can\'t be cached for static pages');
        }

        $response = $event->getResponse ();

        if (!$this->fullPageCacheListener->responseCanBeCached ($response)) {
            $this->fullPageCacheListener->disable ('Response can\'t be cached');
        }

        // check if targeting matched anything and disable cache
        if ($this->disabledByTargeting ()) {
            $this->fullPageCacheListener->disable ('Targeting matched rules/target groups');
        }
    }

    public function disabledByTargeting (): bool
    {

        if (!$this->visitorInfoStorage->hasVisitorInfo ()) {
            return false;
        }

        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo ();

        if (!empty($visitorInfo->getMatchingTargetingRules ())) {
            return true;
        }

        if (!empty($visitorInfo->getTargetGroupAssignments ())) {
            return true;
        }

        return false;

    }
}
