<?php

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
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Document\Renderer\DocumentRenderer;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
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

    /**
     * @param DocumentRenderer $documentRenderer
     * @param Connection $db
     * @param Config $config
     * @param Document\Service $documentService
     * @param SiteResolver $siteResolver
     */
    public function __construct(
        protected DocumentRenderer $documentRenderer,
        protected Connection $db,
        protected Config $config,
        protected Document\Service $documentService,
        protected SiteResolver $siteResolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event)
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

    protected function handleErrorPage(ExceptionEvent $event)
    {
        if (\Pimcore::inDebugMode()) {
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

        $this->logToHttpErrorLog($event->getRequest(), $statusCode);

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
        } catch (\Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = 'Page not found. 🦄';
            $this->logger->emergency('Unable to render error page, exception thrown');
            $this->logger->emergency($e);
        }

        $event->setResponse(new Response($response, $statusCode, $headers));
    }

    protected function logToHttpErrorLog(Request $request, $statusCode)
    {
        $uri = $request->getUri();
        $exists = $this->db->fetchOne('SELECT date FROM http_error_log WHERE uri = ?', [$uri]);
        if ($exists) {
            $this->db->executeQuery('UPDATE http_error_log SET `count` = `count` + 1, date = ? WHERE uri = ?', [time(), $uri]);
        } else {
            $this->db->insert('http_error_log', [
                'uri' => $uri,
                'code' => (int) $statusCode,
                'parametersGet' => serialize($_GET),
                'parametersPost' => serialize($_POST),
                'cookies' => serialize($_COOKIE),
                'serverVars' => serialize($_SERVER),
                'date' => time(),
                'count' => 1,
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     *
     * @throws \Exception
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

        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            $localizedErrorDocumentsPaths = $site->getLocalizedErrorDocuments() ?: [];
            $defaultErrorDocumentPath = $site->getErrorDocument();
        } else {
            $localizedErrorDocumentsPaths = $this->config['documents']['error_pages']['localized'] ?: [];
            $defaultErrorDocumentPath = $this->config['documents']['error_pages']['default'] ?: '';
        }

        if (!empty($locale) && array_key_exists($locale, $localizedErrorDocumentsPaths)) {
            $errorPath = $localizedErrorDocumentsPaths[$locale];
        } else {
            // If locale can't be determined check if error page is defined for any of user-agent preferences
            foreach ($request->getLanguages() as $requestLocale) {
                if (!empty($localizedErrorDocumentsPaths[$requestLocale])) {
                    $errorPath = $this->config['documents']['error_pages']['localized'][$requestLocale];

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
