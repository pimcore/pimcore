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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Exception;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * @internal
 */
class GlobalTemplateVariablesListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    protected array $globalsStack = [];

    public function __construct(
        protected DocumentResolver $documentResolver,
        protected EditmodeResolver $editmodeResolver,
        protected Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 15], // has to be after DocumentFallbackListener
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $globals = $this->twig->getGlobals();

        try {
            // it could be the case that the Twig environment is already initialized at this point
            // then it's not possible anymore to add globals
            $this->twig->addGlobal('document', $this->documentResolver->getDocument($request));
            $this->twig->addGlobal('editmode', $this->editmodeResolver->isEditmode($request));
            $this->globalsStack[] = $globals;
        } catch (Exception) {
            $this->globalsStack[] = false;
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (count($this->globalsStack)) {
            $globals = array_pop($this->globalsStack);
            if ($globals !== false) {
                $this->twig->addGlobal('document', $globals['document'] ?? null);
                $this->twig->addGlobal('editmode', $globals['editmode'] ?? false);
            }
        }
    }
}
