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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Tool;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Environment implements EnvironmentInterface
{
    const USER_ID_NOT_SET = -1;

    protected LocaleServiceInterface $localeService;

    protected Currency $defaultCurrency;

    protected array $customItems = [];

    protected int $userId = self::USER_ID_NOT_SET;

    protected ?bool $useGuestCart = null;

    protected ?string $currentAssortmentTenant = null;

    protected ?string $currentAssortmentSubTenant = null;

    protected ?string $currentCheckoutTenant = null;

    /**
     * Current transient checkout tenant
     *
     * This value will not be stored into the session and is only valid for current process
     * set with setCurrentCheckoutTenant('tenant', false');
     *
     * @var string|null
     */
    protected ?string $currentTransientCheckoutTenant = null;

    public function __construct(LocaleServiceInterface $localeService, array $options = [])
    {
        $this->localeService = $localeService;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        $this->defaultCurrency = new Currency((string)$options['defaultCurrency']);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['defaultCurrency']);
        $resolver->setAllowedTypes('defaultCurrency', 'string');
        $resolver->setDefaults(['defaultCurrency' => 'EUR']);
    }

    protected function load()
    {
    }

    public function save(): mixed
    {
        return $this;
    }

    public function getAllCustomItems(): array
    {
        $this->load();

        return $this->customItems;
    }

    public function getCustomItem(string $key, mixed $defaultValue = null): mixed
    {
        $this->load();

        if (isset($this->customItems[$key])) {
            return $this->customItems[$key];
        }

        return $defaultValue;
    }

    public function setCustomItem(string $key, mixed $value)
    {
        $this->load();

        $this->customItems[$key] = $value;
    }

    public function getCurrentUserId(): int
    {
        $this->load();

        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setCurrentUserId(int $userId): static
    {
        $this->load();

        $this->userId = (int)$userId;

        return $this;
    }

    public function hasCurrentUserId(): bool
    {
        $this->load();

        return $this->getCurrentUserId() !== self::USER_ID_NOT_SET;
    }

    public function removeCustomItem(string $key)
    {
        $this->load();

        unset($this->customItems[$key]);
    }

    public function clearEnvironment()
    {
        $this->load();

        $this->customItems = [];
        $this->userId = self::USER_ID_NOT_SET;
        $this->currentAssortmentTenant = null;
        $this->currentAssortmentSubTenant = null;
        $this->currentCheckoutTenant = null;
        $this->currentTransientCheckoutTenant = null;
        $this->useGuestCart = null;
    }

    public function setDefaultCurrency(Currency $currency)
    {
        $this->defaultCurrency = $currency;
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }

    public function getUseGuestCart(): bool
    {
        if (null === $this->useGuestCart) {
            return !$this->hasCurrentUserId();
        }

        return $this->useGuestCart;
    }

    public function setUseGuestCart(bool $useGuestCart)
    {
        $this->load();

        $this->useGuestCart = (bool)$useGuestCart;
    }

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param string|null $tenant
     */
    public function setCurrentAssortmentTenant(?string $tenant)
    {
        $this->load();

        $this->currentAssortmentTenant = $tenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentTenant(): ?string
    {
        $this->load();

        return $this->currentAssortmentTenant;
    }

    /**
     * sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param string|null $subTenant
     */
    public function setCurrentAssortmentSubTenant(?string $subTenant)
    {
        $this->load();

        $this->currentAssortmentSubTenant = $subTenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentSubTenant(): ?string
    {
        $this->load();

        return $this->currentAssortmentSubTenant;
    }

    /**
     * sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     */
    public function setCurrentCheckoutTenant(string $tenant, bool $persistent = true)
    {
        $this->load();

        if ($this->currentCheckoutTenant != $tenant) {
            if ($persistent) {
                $this->currentCheckoutTenant = $tenant;
            }
            $this->currentTransientCheckoutTenant = $tenant;
        }
    }

    /**
     * gets current assortment tenant which is used for cart and checkout manager
     *
     * @return string|null
     */
    public function getCurrentCheckoutTenant(): ?string
    {
        $this->load();

        return $this->currentTransientCheckoutTenant;
    }

    /**
     * gets current system locale
     *
     * @return null|string
     */
    public function getSystemLocale(): ?string
    {
        $locale = $this->localeService->findLocale();
        if (Tool::isValidLanguage($locale)) {
            return (string)$locale;
        }

        return Tool::getDefaultLanguage();
    }
}
