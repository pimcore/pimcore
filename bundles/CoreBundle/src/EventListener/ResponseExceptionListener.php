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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Doctrine\DBAL\Connection;
use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\SystemSettingsConfig;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class ResponseExceptionListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    public function __construct(
        protected DocumentRendererInterface $documentRenderer,
        protected Connection $db,
        protected SystemSettingsConfig $config,
        protected Document\Service $documentService,
        protected SiteResolver $siteResolver
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());

            // a response was explicitely set -> do not continue to error page
            return;
        }

        // further checks are only valid for default context
        $request = $event->getRequest();
        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            $this->handleErrorPage($event);
        }
    }

    protected function handleErrorPage(ExceptionEvent $event): void
    {
        if (Pimcore::inDebugMode()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $statusCode = 500;
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } else {
            // only log exception if it's not intentional (like a NotFoundHttpException)
            $this->logger->error((string) $exception);
        }

        $errorPath = $this->determineErrorPath($request);

        // Error page rendering
        if (empty($errorPath)) {
            // if not set, use Symfony error handling
            return;
        }

        $document = Document\Page::getByPath($errorPath);

        if (!$document) {
            // default is home
            $document = Document\Page::getById(1);
        }

        try {
            $response = $this->documentRenderer->render($document, [
                'exception' => $exception,
                PimcoreContextListener::ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING => true,
            ]);
        } catch (Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = 'Page not found. 🦄';
            $this->logger->emergency('Unable to render error page, exception thrown');
            $this->logger->emergency((string)$e);
        }

        $event->setResponse(new Response($response, $statusCode, $headers));
    }

    /**
     * @throws Exception
     */
    private function determineErrorPath(Request $request): string
    {
        $errorPath = '';

        if ($this->siteResolver->isSiteRequest($request)) {
            $path = $this->siteResolver->getSitePath($request);
        } else {
            $path = urldecode($request->getPathInfo());
        }

        // Find nearest document by path
        $document = $this->documentService->getNearestDocumentByPath(
            $path,
            false,
            ['page', 'snippet', 'hardlink', 'link', 'folder']
        );

        if ($document && $document->getFullPath() !== '/') {
            if ($document->getProperty('language')) {
                $locale = $document->getProperty('language');
            }
        }

        $config = $this->config->getSystemSettingsConfig();
        $localizedErrorDocumentsPaths = $config['documents']['error_pages']['localized'] ?? null;
        $defaultErrorDocumentPath = $config['documents']['error_pages']['default'] ?? null;

        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            $localizedErrorDocumentsPaths = $site->getLocalizedErrorDocuments();
            $defaultErrorDocumentPath = $site->getErrorDocument();
        }

        $localizedErrorDocumentsPaths = $localizedErrorDocumentsPaths ?: [];
        $defaultErrorDocumentPath = $defaultErrorDocumentPath ?: '';

        if (!empty($locale) && array_key_exists($locale, $localizedErrorDocumentsPaths)) {
            $errorPath = $localizedErrorDocumentsPaths[$locale];
        } else {
            // If locale can't be determined check if error page is defined for any of user-agent preferences
            foreach ($request->getLanguages() as $requestLocale) {
                if (!empty($localizedErrorDocumentsPaths[$requestLocale])) {
                    $errorPath = $localizedErrorDocumentsPaths[$requestLocale];

                    break;
                }
            }
        }

        if (empty($errorPath)) {
            $errorPath = $defaultErrorDocumentPath;
        }

        return $errorPath;
    }
}
