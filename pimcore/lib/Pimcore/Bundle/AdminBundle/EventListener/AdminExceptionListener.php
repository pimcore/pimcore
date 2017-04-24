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

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Service\Request\PimcoreContextResolverAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminExceptionListener implements EventSubscriberInterface, PimcoreContextResolverAwareInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
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
        $ex      = $event->getException();

        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            // only return JSON error for XHR requests
            if (!$request->isXmlHttpRequest()) {
                return;
            }

            list($code, $headers, $message) = $this->getResponseData($ex);

            $data = [
                'success' => false,
                'message' => $message,
            ];

            if (\Pimcore::inDebugMode()) {
                $data['trace'] = $ex->getTrace();
            }

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);

            return;
        } elseif ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_WEBSERVICE)) {
            list($code, $headers, $message) = $this->getResponseData($ex);

            $data = [
                'success' => false,
                'msg'     => $message
            ];

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);

            return;
        }
    }

    private function getResponseData(\Exception $ex, int $defaultStatusCode = 500): array
    {
        $code    = $defaultStatusCode;
        $headers = [];

        $message = $ex->getMessage();

        if ($ex instanceof HttpExceptionInterface) {
            if (empty($message)) {
                $message = Response::$statusTexts[$ex->getStatusCode()];
            }

            $code    = $ex->getStatusCode();
            $headers = $ex->getHeaders();
        }

        return [$code, $headers, $message];
    }
}
