<?php

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

namespace Pimcore\Bundle\AdminBundle\Security\Authenticator;

use Pimcore\Model\User;
use Pimcore\Model\User as UserModel;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class AdminSessionAuthenticator extends AdminAbstractAuthenticator
{
    /**
     * @var User|null
     */
    protected ?User $user;

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        $this->user = Authentication::authenticateSession($request);

        return (bool) $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request): Passport
    {
        if (!$this->user instanceof UserModel) {
            throw new AuthenticationException('Invalid User!');
        }

        $session = Session::getReadOnly();
        if ($session->has('2fa_required') && $session->get('2fa_required') === true) {
            $this->twoFactorRequired = true;
        }

        $badges = [
            new PreAuthenticatedUserBadge(),
        ];

        return new SelfValidatingPassport(
            new UserBadge($this->user->getUsername()),
            $badges
        );
    }
}
