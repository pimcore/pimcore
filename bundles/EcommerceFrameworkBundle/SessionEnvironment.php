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

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\EventListener\SessionBagListener;
use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionEnvironment extends Environment implements EnvironmentInterface
{
    const SESSION_KEY_CUSTOM_ITEMS = 'customitems';

    const SESSION_KEY_USERID = 'userid';

    const SESSION_KEY_USE_GUEST_CART = 'useguestcart';

    const SESSION_KEY_ASSORTMENT_TENANT = 'currentassortmenttenant';

    const SESSION_KEY_ASSORTMENT_SUB_TENANT = 'currentassortmentsubtenant';

    const SESSION_KEY_CHECKOUT_TENANT = 'currentcheckouttenant';

    /**
     *
     * @deprecated will be removed in Pimcore 11
     *
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * @var bool
     */
    protected $sessionLoaded = false;

    public function __construct(SessionInterface $session, LocaleServiceInterface $localeService, array $options = [])
    {
        parent::__construct($localeService, $options);

        $this->session = $session;
    }

    /**
     * @TODO move to constructor injection in Pimcore 11
     *
     * @required
     *
     * @internal
     *
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    protected function load()
    {
        if ($this->sessionLoaded) {
            return;
        }

        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->checkSessionStartedInCli()) {
            return;
        }

        $sessionBag = $this->getSessionBag();

        $this->customItems = $sessionBag->get(self::SESSION_KEY_CUSTOM_ITEMS, []);

        $this->userId = $sessionBag->get(self::SESSION_KEY_USERID, self::USER_ID_NOT_SET);

        $this->currentAssortmentTenant = $sessionBag->get(self::SESSION_KEY_ASSORTMENT_TENANT);
        $this->currentAssortmentSubTenant = $sessionBag->get(self::SESSION_KEY_ASSORTMENT_SUB_TENANT);

        $this->currentCheckoutTenant = $sessionBag->get(self::SESSION_KEY_CHECKOUT_TENANT);
        $this->currentTransientCheckoutTenant = $sessionBag->get(self::SESSION_KEY_CHECKOUT_TENANT);

        $this->useGuestCart = $sessionBag->get(self::SESSION_KEY_USE_GUEST_CART);

        $this->sessionLoaded = true;
    }

    public function save()
    {
        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->checkSessionStartedInCli()) {
            return;
        }

        $this->load();

        $sessionBag = $this->getSessionBag();
        $sessionBag->set(self::SESSION_KEY_CUSTOM_ITEMS, $this->customItems);
        $sessionBag->set(self::SESSION_KEY_USERID, $this->userId);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_TENANT, $this->currentAssortmentTenant);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_SUB_TENANT, $this->currentAssortmentSubTenant);
        $sessionBag->set(self::SESSION_KEY_CHECKOUT_TENANT, $this->currentCheckoutTenant);
        $sessionBag->set(self::SESSION_KEY_USE_GUEST_CART, $this->useGuestCart);
    }

    public function clearEnvironment()
    {
        parent::clearEnvironment();

        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->checkSessionStartedInCli()) {
            return;
        }

        $this->load();

        $sessionBag = $this->getSessionBag();

        $sessionBag->remove(self::SESSION_KEY_CUSTOM_ITEMS);
        $sessionBag->remove(self::SESSION_KEY_USERID);
        $sessionBag->remove(self::SESSION_KEY_USE_GUEST_CART);
        $sessionBag->remove(self::SESSION_KEY_ASSORTMENT_TENANT);
        $sessionBag->remove(self::SESSION_KEY_ASSORTMENT_SUB_TENANT);
        $sessionBag->remove(self::SESSION_KEY_CHECKOUT_TENANT);
    }

    /**
     * @return AttributeBagInterface
     */
    protected function getSessionBag(): AttributeBagInterface
    {
        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $this->getSession()->getBag(SessionBagListener::ATTRIBUTE_BAG_ENVIRONMENT);

        return $sessionBag;
    }

    private function getSession(bool $preventDeprecationWarning = false): SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            if (!$preventDeprecationWarning) {
                trigger_deprecation('pimcore/pimcore', '10.5',
                    sprintf('Session used with non existing request stack in %s, that will not be possible in Pimcore 11.', SessionEnvironment::class));
            }

            return \Pimcore::getContainer()->get('session');
        }
    }

    /**
     * @deprecated
     *
     * @todo Remove in Pimcore 11
     */
    private function checkSessionStartedInCli(): bool
    {
        $session = $this->getSession(true);
        if ($session->isStarted()) {
            trigger_deprecation('pimcore/pimcore', '10.5',
                sprintf('Do not depend on started session in CLI mode for %s, that will not be possible in Pimcore 11.', SessionEnvironment::class));

            return true;
        }

        return false;
    }
}
