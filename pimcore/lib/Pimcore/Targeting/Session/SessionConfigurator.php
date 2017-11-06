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
    const TARGETING_BAG = 'pimcore_targeting';

    public function configure(SessionInterface $session)
    {
        $bag = new NamespacedAttributeBag('_' . self::TARGETING_BAG);
        $bag->setName(self::TARGETING_BAG);

        $session->registerBag($bag);
    }
}
