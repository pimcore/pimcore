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

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Document\Renderer\DocumentRenderer;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Templating\Renderer\ActionRenderer;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    /**
     * @var ActionRenderer
     */
    protected $documentRenderer;

    /**
     * @var bool
     */
    protected $renderErrorPage = true;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @param DocumentRenderer $documentRenderer
     * @param ConnectionInterface $db
     * @param bool $renderErrorPage
     */
    public function __construct(DocumentRenderer $documentRenderer, ConnectionInterface $db, Config $config, $renderErrorPage = true)
    {
        $this->documentRenderer = $documentRenderer;
        $this->renderErrorPage = (bool)$renderErrorPage;
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());

            // a response was explicitely set -> do not continue to error page
            return;
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
        if (\Pimcore::inDebugMode()) {
            return;
        }

        $exception = $event->getException();

        $statusCode = 500;
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } else {
            // only log exception if it's not intentional (like a NotFoundHttpException)
            $this->logger->error($exception);
        }

        $errorPath = $this->config['documents']['error_pages']['default'];

        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            $errorPath = $site->getErrorDocument();
        }

        $this->logToHttpErrorLog($event->getRequest(), $statusCode);

        // Error page rendering
        if (empty($errorPath)) {
            $errorPath = '/';
        }

        $document = Document::getByPath($errorPath);

        if (!$document instanceof Document\Page) {
            // default is home
            $document = Document::getById(1);
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
        $exists = $this->db->fetchOne('SELECT date FROM http_error_log WHERE uri = ?', $uri);
        if ($exists) {
            $this->db->query('UPDATE http_error_log SET `count` = `count` + 1, date = ? WHERE uri = ?', [time(), $uri]);
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
}
