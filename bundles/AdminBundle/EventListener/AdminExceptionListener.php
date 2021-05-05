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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Doctrine\DBAL\Exception as DBALException;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class AdminExceptionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        $ex = $event->getThrowable();

        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            // only return JSON error for XHR requests
            if (!$request->isXmlHttpRequest()) {
                return;
            }

            list($code, $headers, $message) = $this->getResponseData($ex);

            $data = [
                'success' => false,
            ];

            if (!\Pimcore::inDebugMode()) {
                // DBAL exceptions do include SQL statements, we don't want to expose them
                if ($ex instanceof DBALException) {
                    $message = 'Database error, see logs for details';
                }
            }

            if (\Pimcore::inDebugMode()) {
                $data['trace'] = $ex->getTrace();
                $data['traceString'] = 'in ' . $ex->getFile() . ':' . $ex->getLine() . "\n" . $ex->getTraceAsString();
            }

            if ($ex instanceof ValidationException) {
                $data['type'] = 'ValidationException';
                $code = 422;

                $this->recursiveAddValidationExceptionSubItems($ex->getSubItems(), $message, $data['traceString']);
            }

            $data['message'] = $message;

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);

            return;
        }
    }

    private function getResponseData(\Throwable $ex, int $defaultStatusCode = 500): array
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
     * @param \Throwable $e
     *
     * @return \Throwable
     */
    protected function getInnerStack(\Throwable $e)
    {
        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }

        return $e;
    }
}
