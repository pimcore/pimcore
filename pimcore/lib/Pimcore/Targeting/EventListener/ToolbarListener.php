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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Response\CodeInjector;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\VisitorInfoStorageInterface;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Templating\EngineInterface;

class ToolbarListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var CodeInjector
     */
    private $codeInjector;

    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    public function __construct(
        EngineInterface $templatingEngine,
        CodeInjector $codeInjector,
        VisitorInfoStorageInterface $visitorInfoStorage
    )
    {
        $this->templatingEngine   = $templatingEngine;
        $this->codeInjector       = $codeInjector;
        $this->visitorInfoStorage = $visitorInfoStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -127],
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // only inject toolbar for logged in admin users
        $adminUser = Authentication::authenticateSession($request);
        if (!$adminUser) {
            return;
        }

        // only inject toolbar if there's a visitor info
        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return;
        }

        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();

        $this->injectToolbar(
            $event->getResponse(),
            $visitorInfo
        );
    }

    private function injectToolbar(Response $response, VisitorInfo $visitorInfo)
    {
        $token = substr(hash('sha256', uniqid((string)mt_rand(), true)), 0, 6);
        $code  = $this->templatingEngine->render('@PimcoreCore/Targeting/toolbar/toolbar.html.twig', [
            'token' => $token
        ]);

        $this->codeInjector->inject(
            $response,
            $code,
            CodeInjector::SELECTOR_BODY,
            CodeInjector::POSITION_END
        );
    }
}
