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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Tool\Session;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class TwoFactorListener
{
    public function onAuthenticationComplete(TwoFactorAuthenticationEvent $event)
    {
        // this session flag is set in \Pimcore\Bundle\AdminBundle\Security\Guard\AdminAuthenticator
        // @TODO: check if there's a nicer way of doing this, actually it feels a bit like a hack :)
        Session::useSession(function (AttributeBagInterface $adminSession) {
            $adminSession->set('2fa_required', false);
        });
    }
}
