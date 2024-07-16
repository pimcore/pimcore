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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class ResponseExceptionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(
        protected Connection $db,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run with high priority before handling real errors
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $exception = $event->getThrowable();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            return;
        }

        // further checks are only valid for default context
        $request = $event->getRequest();
        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            if (Pimcore::inDebugMode()) {
                return;
            }

            $exception = $event->getThrowable();

            $statusCode = 500;

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
            }

            $this->logToHttpErrorLog($event->getRequest(), $statusCode);
        }
    }

    protected function logToHttpErrorLog(Request $request, int $statusCode): void
    {
        $uri = $request->getUri();
        $exists = $this->db->fetchOne('SELECT date FROM http_error_log WHERE uri = ?', [$uri]);
        if ($exists) {
            $this->db->executeQuery('UPDATE http_error_log SET `count` = `count` + 1, date = ? WHERE uri = ?', [time(), $uri]);
        } else {
            $this->db->insert('http_error_log', [
                'uri' => $uri,
                'code' => $statusCode,
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
