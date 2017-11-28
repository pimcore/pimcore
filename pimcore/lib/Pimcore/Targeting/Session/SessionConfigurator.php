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

namespace Pimcore\Targeting\Session;

use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionConfigurator implements SessionConfiguratorInterface
{
    const TARGETING_BAG_SESSION = 'pimcore_targeting_session';
    const TARGETING_BAG_VISITOR = 'pimcore_targeting_visitor';

    public function configure(SessionInterface $session)
    {
        $sessionBag = new NamespacedAttributeBag('_' . self::TARGETING_BAG_SESSION);
        $sessionBag->setName(self::TARGETING_BAG_SESSION);

        $visitorBag = new NamespacedAttributeBag('_' . self::TARGETING_BAG_VISITOR);
        $visitorBag->setName(self::TARGETING_BAG_VISITOR);

        $session->registerBag($sessionBag);
        $session->registerBag($visitorBag);
    }

    public static function getTargetingStorageKeys(): array
    {
        return [
            '_' . self::TARGETING_BAG_SESSION,
            '_' . self::TARGETING_BAG_VISITOR
        ];
    }
}
