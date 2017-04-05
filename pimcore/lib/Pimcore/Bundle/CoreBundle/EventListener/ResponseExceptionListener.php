<?php
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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Service\Request\PimcoreContextResolverAwareInterface;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface, PimcoreContextResolverAwareInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @var bool
     */
    protected $renderErrorPage = true;

    /**
     * @param ActionRenderer $actionRenderer
     * @param bool $renderErrorPage
     */
    public function __construct(ActionRenderer $actionRenderer, $renderErrorPage = true)
    {
        $this->actionRenderer  = $actionRenderer;
        $this->renderErrorPage = (bool)$renderErrorPage;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());
        }

        // further checks are only valid for default context
        $request = $event->getRequest();
        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            if ($this->renderErrorPage) {
                $this->handleErrorPage($event);
            }
        }
    }

    protected function handleErrorPage(GetResponseForExceptionEvent $event)
    {
        if (\Pimcore::inDebugMode() || PIMCORE_DEVMODE) {
            return;
        }

        $errorPath = Config::getSystemConfig()->documents->error_pages->default;

        if (Site::isSiteRequest()) {
            $site      = Site::getCurrentSite();
            $errorPath = $site->getErrorDocument();
        }

        if (empty($errorPath)) {
            $errorPath = "/";
        }

        $document = Document::getByPath($errorPath);

        if (!$document instanceof Document\Page) {
            // default is home
            $document = Document::getById(1);
        }

        $controller = $this->actionRenderer->createDocumentReference($document);

        try {
            $response = $this->actionRenderer->render($controller);
        } catch (\Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = "Page not found. 🦄";
        }

        $event->setResponse(new Response($response));
    }
}
