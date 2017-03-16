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

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\ActionRenderer;
use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface
{
    /**
     * @var PimcoreContextResolver
     */
    protected $contextResolver;

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * ResponseExceptionListener constructor.
     * @param PimcoreContextResolver $contextResolver
     * @param ActionRenderer $actionRenderer
     */
    public function __construct(PimcoreContextResolver $contextResolver, ActionRenderer $actionRenderer)
    {
        $this->contextResolver = $contextResolver;
        $this->actionRenderer = $actionRenderer;
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
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());
        }

        if ($this->contextResolver->getPimcoreContext() == PimcoreContextResolver::CONTEXT_DEFAULT) {
            if (!\Pimcore::inDebugMode() && !PIMCORE_DEVMODE && Config::getEnvironment() != "dev") {
                $errorPath = Config::getSystemConfig()->documents->error_pages->default;

                if (Site::isSiteRequest()) {
                    $site = Site::getCurrentSite();
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
    }
}
