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

/**
 * Interface for environment implementations of online shop framework
 */
interface EnvironmentInterface extends ComponentInterface
{
    /**
     * Returns current user id
     *
     * @return int
     */
    public function getCurrentUserId(): int;

    /**
     * Sets current user id
     *
     * @param int $userId
     *
     * @return $this
     */
    public function setCurrentUserId(int $userId): static;

    /**
     * Checks if a user id is set
     *
     * @return bool
     */
    public function hasCurrentUserId(): bool;

    /**
     * Sets custom item to environment - which is saved to the session then
     * save()-call is needed to save the custom items
     *
     * @param string $key
     * @param mixed $value
     */
    public function setCustomItem(string $key, mixed $value): void;

    /**
     * Removes custom item from the environment
     * save()-call is needed to save the custom items
     *
     * @param string $key
     */
    public function removeCustomItem(string $key): void;

    /**
     * Returns custom saved item from environment
     *
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getCustomItem(string $key, mixed $defaultValue = null): mixed;

    /**
     * Returns all custom items from environment
     *
     * @return array
     */
    public function getAllCustomItems(): array;

    /**
     * Resets environment
     * save()-call is needed to save changes
     */
    public function clearEnvironment(): void;

    /**
     * Sets current assortment tenant which is used for indexing and product lists
     *
     * @param string|null $tenant
     */
    public function setCurrentAssortmentTenant(?string $tenant): void;

    /**
     * Returns current assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentTenant(): ?string;

    /**
     * Sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param string|null $subTenant
     */
    public function setCurrentAssortmentSubTenant(?string $subTenant): void;

    /**
     * Returns current sub assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentSubTenant(): ?string;

    /**
     * Sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     */
    public function setCurrentCheckoutTenant(string $tenant, bool $persistent = true): void;

    /**
     * Returns current assortment tenant which is used for cart and checkout manager
     *
     * @return string|null
     */
    public function getCurrentCheckoutTenant(): ?string;

    /**
     * Set the default currency in a multi-currency environment.
     *
     * @param Currency $currency
     */
    public function setDefaultCurrency(Currency $currency): void;

    /**
     * Returns instance of default currency
     *
     * @return Currency
     */
    public function getDefaultCurrency(): Currency;

    public function getUseGuestCart(): bool;

    public function setUseGuestCart(bool $useGuestCart): void;

    /**
     * Returns current system locale
     *
     * @return null|string
     */
    public function getSystemLocale(): ?string;
}
