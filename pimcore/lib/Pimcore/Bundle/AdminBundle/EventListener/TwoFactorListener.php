<?php
/**
 * Created by PhpStorm.
 * User: tmittendorfer
 * Date: 06.06.2018
 * Time: 14:38
 */

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Tool\Session;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class TwoFactorListener
{
    public function onAuthenticationComplete(TwoFactorAuthenticationEvent $event) {
        Session::useSession(function (AttributeBagInterface $adminSession) {
            $adminSession->set('2fa', true);
        });
    }
}