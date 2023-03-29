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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Log\Simple;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class UsageStatisticsListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    protected TokenStorageUserResolver $userResolver;

    protected Config $config;

    public function __construct(TokenStorageUserResolver $userResolver, Config $config)
    {
        $this->userResolver = $userResolver;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $this->logUsageStatistics($request);
    }

    protected function logUsageStatistics(Request $request): void
    {
        if (!empty($this->config['general']['disable_usage_statistics'])) {
            return;
        }

        $params = $this->getParams($request);
        $user = $this->userResolver->getUser();
        $this->logger->info($request->attributes->get('_controller'), [
            $user ? $user->getId() : '0',
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
            $params,
        ]);
    }

    protected function getParams(Request $request): array
    {
        $params = [];
        $disallowedKeys = ['_dc', 'module', 'controller', 'action', 'password'];

        // TODO is this enough?
        $requestParams = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        foreach ($requestParams as $key => $value) {
            if (is_json($value)) {
                $value = json_decode($value);
                if (is_array($value)) {
                    array_walk_recursive($value, function (&$item, $key) {
                        if (strpos((string)$key, 'pass') !== false) {
                            $item = '*************';
                        }
                    });
                }

                $value = json_encode($value);
            }

            if (!in_array($key, $disallowedKeys) && is_string($value)) {
                $params[$key] = (strlen($value) > 40) ? substr($value, 0, 40) . '...' : $value;
            }
        }

        return $params;
    }
}
