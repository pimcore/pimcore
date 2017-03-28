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

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Service\Request\PimcoreContextResolverAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class WebserviceExceptionListener implements EventSubscriberInterface, PimcoreContextResolverAwareInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
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
        if ($this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_WEBSERVICE)) {
            $ex = $event->getException();

            $code = 400;
            $data = [
                'success' => false,
                'msg'     => $ex->getMessage()
            ];

            $headers = [];

            if ($ex instanceof HttpExceptionInterface) {
                if (empty($ex->getMessage())) {
                    $data['msg'] = Response::$statusTexts[$ex->getStatusCode()];
                }

                $code    = $ex->getStatusCode();
                $headers = $ex->getHeaders();
            }

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);
        }
    }
}
