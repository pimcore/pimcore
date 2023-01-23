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

use Psr\Log\LoggerAwareTrait;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @internal
 */
class TwoFactorListener
{
    use LoggerAwareTrait;

    private TwoFactorProviderRegistry $providerRegistry;

    private PreparationRecorderInterface $preparationRecorder;

    public function __construct(TwoFactorProviderRegistry $providerRegistry, PreparationRecorderInterface $preparationRecorder)
    {
        $this->providerRegistry = $providerRegistry;
        $this->preparationRecorder = $preparationRecorder;
    }

    public function onAuthenticationAttempt(TwoFactorAuthenticationEvent $event): void
    {
        $twoFactorToken = $event->getToken();
        if (!$twoFactorToken instanceof TwoFactorTokenInterface) {
            return;
        }

        $providerName = $twoFactorToken->getCurrentTwoFactorProvider();
        if (null === $providerName) {
            return;
        }

        $twoFactorToken->setTwoFactorProviderPrepared($providerName);
        /** @var PostAuthenticationToken $twoFactorAuthenticatedToken */
        $twoFactorAuthenticatedToken = $twoFactorToken->getAuthenticatedToken();
        $firewallName = $twoFactorAuthenticatedToken->getFirewallName();

        if ($this->preparationRecorder->isTwoFactorProviderPrepared($firewallName, $providerName)) {
            $this->logger->info(sprintf('Two-factor provider "%s" was already prepared.', $providerName));

            return;
        }

        $user = $twoFactorToken->getUser();
        $this->providerRegistry->getProvider($providerName)->prepareAuthentication($user);

        $this->preparationRecorder->setTwoFactorProviderPrepared($firewallName, $providerName);
        $this->logger->info(sprintf('Two-factor provider "%s" prepared.', $providerName));
    }
}
