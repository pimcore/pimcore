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

namespace Pimcore\Targeting\Session;

use Pimcore\Session\SessionConfiguratorInterface;
use Pimcore\Targeting\EventListener\TargetingSessionBagListener;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @deprecated will be removed in Pimcore 11. Use TargetingSessionBagListener instead.
 */
class SessionConfigurator extends TargetingSessionBagListener implements SessionConfiguratorInterface
{
    public function configure(SessionInterface $session)
    {
    }
}
