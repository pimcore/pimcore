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

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Log\Simple;
use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UsageStatisticsListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var TokenStorageUserResolver
     */
    protected $userResolver;

    /**
     * @param TokenStorageUserResolver $userResolver
     */
    public function __construct(TokenStorageUserResolver $userResolver)
    {
        $this->userResolver = $userResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $this->logUsageStatistics($request);
    }

    /**
     * @param Request $request
     */
    protected function logUsageStatistics(Request $request)
    {
        $params = $this->getParams($request);
        $user   = $this->userResolver->getUser();

        $parts = [
            $user ? $user->getId() : '0',
            $request->attributes->get('_controller'),
            $request->attributes->get('_route'),
            @json_encode($request->attributes->get('_route_params')),
            @json_encode($params)
        ];

        Simple::log('usagelog', implode('|', $parts));
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getParams(Request $request)
    {
        $params = [];
        $disallowedKeys = ["_dc", "module", "controller", "action", "password"];

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
                        if (strpos($key, "pass") !== false) {
                            $item = "*************";
                        }
                    });
                }

                $value = json_encode($value);
            }

            if (!in_array($key, $disallowedKeys) && is_string($value)) {
                $params[$key] = (strlen($value) > 40) ? substr($value, 0, 40) . "..." : $value;
            }
        }

        return $params;
    }
}
