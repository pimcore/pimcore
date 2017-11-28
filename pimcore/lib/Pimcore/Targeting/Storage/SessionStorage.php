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

namespace Pimcore\Targeting\Storage;

use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Session\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class SessionStorage implements TargetingStorageInterface
{
    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        $this->validateScope($scope);

        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return false;
        }

        return $bag->has($name);
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        $this->validateScope($scope);

        $bag = $this->getSessionBag($visitorInfo);
        if (null === $bag) {
            return;
        }

        $bag->set($name, $value);
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        $this->validateScope($scope);

        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return $default;
        }

        return $bag->get($name, $default);
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        $this->validateScope($scope);

        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return [];
        }

        return $bag->all();
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        if (null !== $scope) {
            $this->validateScope($scope);
        }

        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return;
        }

        $bag->clear();
    }

    private function validateScope(string $scope)
    {
        if (self::SCOPE_SESSION !== $scope) {
            throw new \LogicException(sprintf('The session storage is not able to handle the "%s" scope', $scope));
        }
    }

    /**
     * @param VisitorInfo $visitorInfo
     * @param bool $checkPreviousSession
     *
     * @return null|NamespacedAttributeBag
     */
    private function getSessionBag(VisitorInfo $visitorInfo, bool $checkPreviousSession = false)
    {
        $request = $visitorInfo->getRequest();

        if (!$request->hasSession()) {
            return null;
        }

        if ($checkPreviousSession && !$request->hasPreviousSession()) {
            return null;
        }

        $session = $request->getSession();

        /** @var NamespacedAttributeBag $bag */
        $bag = $session->getBag(SessionConfigurator::TARGETING_BAG);

        return $bag;
    }
}
