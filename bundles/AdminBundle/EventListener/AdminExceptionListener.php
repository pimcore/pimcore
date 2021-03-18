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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminExceptionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Return JSON error responses from webservice context
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $ex = $event->getException();

        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            // only return JSON error for XHR requests
            if (!$request->isXmlHttpRequest()) {
                return;
            }

            list($code, $headers, $message) = $this->getResponseData($ex);

            if (!\Pimcore::inDebugMode()) {
                // DBAL exceptions do include SQL statements, we don't want to expose them
                if ($ex instanceof DBALException) {
                    $message = 'Database error, see logs for details';
                }
            }

            $data = [
                'success' => false,
                'message' => $message,
            ];

            if (\Pimcore::inDebugMode()) {
                $data['trace'] = $ex->getTrace();
                $data['traceString'] = 'in ' . $ex->getFile() . ':' . $ex->getLine() . "\n" . $ex->getTraceAsString();
            }

            if ($ex instanceof ValidationException) {
                $data['type'] = 'ValidationException';
                $code = 422;

                $message = '';
                $this->recursiveAddValidationExceptionSubItems($ex->getSubItems(), $message, $data['traceString']);
                $data['message'] = $message;
            }

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);

            return;
        } elseif ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_WEBSERVICE)) {
            list($code, $headers, $message) = $this->getResponseData($ex);

            if ($ex instanceof DBALException) {
                $message = 'Database error, see logs for details';
            }

            $data = [
                'success' => false,
                'msg' => $message,
            ];

            if (\Pimcore::inDebugMode()) {
                $data['trace'] = $ex->getTrace();
                $data['traceString'] = $ex->getTraceAsString();
            }

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);

            return;
        }
    }

    private function getResponseData(\Exception $ex, int $defaultStatusCode = 500): array
    {
        $code = $defaultStatusCode;
        $headers = [];

        $message = $ex->getMessage();

        if ($ex instanceof HttpExceptionInterface) {
            if (empty($message)) {
                $message = Response::$statusTexts[$ex->getStatusCode()];
            }

            $code = $ex->getStatusCode();
            $headers = $ex->getHeaders();
        }

        return [$code, $headers, $message];
    }

    /**
     * @param ValidationException[] $items
     * @param string $message
     * @param string $detailedInfo
     */
    protected function recursiveAddValidationExceptionSubItems($items, &$message, &$detailedInfo)
    {
        if (!$items) {
            return;
        }
        foreach ($items as $e) {
            if ($e->getMessage()) {
                $message .= '<b>' . $e->getMessage() . '</b>';
                $this->addContext($e, $message);
                $message .= '<br>';

                $detailedInfo .= '<br><b>Message:</b><br>';
                $detailedInfo .= $e->getMessage() . '<br>';

                $inner = $this->getInnerStack($e);
                $detailedInfo .= '<br><b>Trace:</b> ' . $inner->getTraceAsString() . '<br>';
            }

            $this->recursiveAddValidationExceptionSubItems($e->getSubItems(), $message, $detailedInfo);
        }
    }

    /**
     * @param ValidationException $e
     * @param string $message
     */
    protected function addContext(ValidationException $e, &$message)
    {
        $contextStack = $e->getContextStack();
        if ($contextStack) {
            $message = $message . ' (' . implode(',', $contextStack) . ')';
        }
    }

    /**
     * @param \Exception $e
     *
     * @return \Exception
     */
    protected function getInnerStack(\Exception $e)
    {
        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }

        return $e;
    }
}
