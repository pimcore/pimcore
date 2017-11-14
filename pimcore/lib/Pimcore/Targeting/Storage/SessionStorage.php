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
    public function has(VisitorInfo $visitorInfo, string $name): bool
    {
        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return false;
        }

        return $bag->has($name);
    }

    public function set(VisitorInfo $visitorInfo, string $name, $value)
    {
        $bag = $this->getSessionBag($visitorInfo);
        if (null === $bag) {
            return;
        }

        $bag->set($name, $value);
    }

    public function get(VisitorInfo $visitorInfo, string $name, $default = null)
    {
        $bag = $this->getSessionBag($visitorInfo, true);
        if (null === $bag) {
            return $default;
        }

        return $bag->get($name, $default);
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
