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

namespace Pimcore\Model;

use Pimcore\Model\User\UserRoleInterface;

interface UserInterface extends UserRoleInterface
{
    public function getPassword(): ?string;

    /**
     * @return $this
     */
    public function setPassword(?string $password): static;

    /**
     * Alias for getName()
     *
     */
    public function getUsername(): ?string;

    /**
     * @return $this
     */
    public function setUsername(?string $username): static;

    public function getFirstname(): ?string;

    /**
     * @return $this
     */
    public function setFirstname(?string $firstname): static;

    public function getLastname(): ?string;

    /**
     * @return $this
     */
    public function setLastname(?string $lastname): static;

    public function getFullName(): string;

    public function getEmail(): ?string;

    /**
     * @return $this
     */
    public function setEmail(?string $email): static;

    public function getLanguage(): string;

    /**
     * @return $this
     */
    public function setLanguage(string $language): static;

    /**
     * @see getAdmin()
     *
     */
    public function isAdmin(): bool;

    public function getAdmin(): bool;

    /**
     * @return $this
     */
    public function setAdmin(bool $admin): static;

    public function getActive(): bool;

    /**
     * @return $this
     */
    public function setActive(bool $active): static;

    public function isActive(): bool;

    public function isAllowed(string $key, string $type = 'permission'): bool;

    /**
     * @param int[]|string $roles
     *
     * @return $this
     */
    public function setRoles(array|string $roles): static;

    /**
     * @return int[]
     */
    public function getRoles(): array;

    /**
     * @return $this
     */
    public function setWelcomescreen(bool $welcomescreen): static;

    public function getWelcomescreen(): bool;

    /**
     * @return $this
     */
    public function setCloseWarning(bool $closeWarning): static;

    public function getCloseWarning(): bool;

    /**
     * @return $this
     */
    public function setMemorizeTabs(bool $memorizeTabs): static;

    public function getMemorizeTabs(): bool;

    /**
     * @return $this
     */
    public function setAllowDirtyClose(bool $allowDirtyClose): static;

    public function getAllowDirtyClose(): bool;

    public function setImage(?string $path): void;

    /**
     *
     * @return resource
     */
    public function getImage(?int $width = null, ?int $height = null);

    /**
     * @return string[]
     */
    public function getContentLanguages(): array;

    /**
     * @param string[]|string|null $contentLanguages
     */
    public function setContentLanguages(array|string|null $contentLanguages): void;

    public function getActivePerspective(): string;

    public function setActivePerspective(?string $activePerspective): void;

    /**
     * Returns the first perspective name
     *
     * @internal
     */
    public function getFirstAllowedPerspective(): string;

    /**
     * Returns array of languages allowed for editing. If edit and view languages are empty all languages are allowed.
     * If only edit languages are empty (but view languages not) empty array is returned.
     *
     * @return string[]|null
     *
     * @internal
     *
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations(): ?array;

    /**
     * Returns array of languages allowed for viewing. If view languages are empty all languages are allowed.
     *
     * @return string[]|null
     *
     * @internal
     *
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations(): ?array;

    public function getLastLogin(): ?int;

    /**
     * @return $this
     */
    public function setLastLogin(int $lastLogin): static;

    public function getKeyBindings(): ?string;

    public function setKeyBindings(string $keyBindings): void;

    public function getTwoFactorAuthentication(string $key = null): mixed;

    /**
     * You can either pass an array for setting the entire 2fa settings, or a key and a value as the second argument
     *
     * @param array<string, mixed>|string $key
     */
    public function setTwoFactorAuthentication(array|string $key, mixed $value = null): void;

    public function getProvider(): ?string;

    public function setProvider(?string $provider): void;

    public function hasImage(): bool;
}
