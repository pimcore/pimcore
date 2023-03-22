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

use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Model\User\Role;
use Pimcore\Tool;

/**
 * @method User\Dao getDao()
 */
final class User extends User\UserRole
{
    use TemporaryFileHelperTrait;

    protected const DEFAULT_KEY_BINDINGS = 'default_key_bindings';

    protected string $type = 'user';

    protected ?string $password = null;

    protected ?string $firstname = null;

    protected ?string $lastname = null;

    protected ?string $email = null;

    protected string $language = 'en';

    protected bool $admin = false;

    protected bool $active = true;

    /**
     * @var int[]
     */
    protected array $roles = [];

    protected bool $welcomescreen = false;

    protected bool $closeWarning = true;

    protected bool $memorizeTabs = true;

    protected bool $allowDirtyClose = false;

    protected ?string $contentLanguages = '';

    protected ?string $activePerspective = null;

    /**
     * @var string[]|null
     */
    protected ?array $mergedPerspectives = null;

    /**
     * @var string[]|null
     */
    protected ?array $mergedWebsiteTranslationLanguagesEdit = null;

    /**
     * @var string[]|null
     */
    protected ?array $mergedWebsiteTranslationLanguagesView = null;

    protected int $lastLogin;

    protected ?string $keyBindings = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $twoFactorAuthentication = null;

    /**
     * OIDC Provider from pimcore/openid-connect
     */
    protected ?string $provider = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return $this
     */
    public function setPassword(?string $password): static
    {
        if (strlen((string) $password) > 4) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Alias for getName()
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getName();
    }

    /**
     * @return $this
     */
    public function setUsername(?string $username): static
    {
        $this->setName($username);

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @return $this
     */
    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @return $this
     */
    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->getFirstname() . ' ' . $this->getLastname());
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return $this
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setLanguage(string $language): static
    {
        if ($language) {
            $this->language = $language;
        }

        return $this;
    }

    /**
     * @see getAdmin()
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->getAdmin();
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @return $this
     */
    public function setAdmin(bool $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->getActive();
    }

    public function isAllowed(string $key, string $type = 'permission'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($type == 'permission') {
            if (!$this->getPermission($key)) {
                // check roles
                foreach ($this->getRoles() as $roleId) {
                    /** @var Role $role */
                    $role = User\Role::getById($roleId);
                    if ($role->getPermission($key)) {
                        return true;
                    }
                }
            }

            return $this->getPermission($key);
        } elseif ($type == 'class') {
            $classes = $this->getClasses();
            foreach ($this->getRoles() as $roleId) {
                /** @var Role $role */
                $role = User\Role::getById($roleId);
                $classes = array_merge($classes, $role->getClasses());
            }

            if (!empty($classes)) {
                return in_array($key, $classes);
            } else {
                return true;
            }
        } elseif ($type == 'docType') {
            $docTypes = $this->getDocTypes();
            foreach ($this->getRoles() as $roleId) {
                /** @var Role $role */
                $role = User\Role::getById($roleId);
                $docTypes = array_merge($docTypes, $role->getDocTypes());
            }

            if (!empty($docTypes)) {
                return in_array($key, $docTypes);
            } else {
                return true;
            }
        } elseif ($type == 'perspective') {
            //returns true if required perspective is allowed to use by the user
            return in_array($key, $this->getMergedPerspectives());
        }

        return false;
    }

    public function getPermission(string $permissionName): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return parent::getPermission($permissionName);
    }

    /**
     * @param int[]|string $roles
     *
     * @return $this
     */
    public function setRoles(array|string $roles): static
    {
        if (is_string($roles) && $roles !== '') {
            $this->roles = array_map('intval', explode(',', $roles));
        } elseif (is_array($roles)) {
            $this->roles = array_map('intval', $roles);
        } else {
            $this->roles = [];
        }

        return $this;
    }

    /**
     * @return int[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return $this
     */
    public function setWelcomescreen(bool $welcomescreen): static
    {
        $this->welcomescreen = (bool)$welcomescreen;

        return $this;
    }

    public function getWelcomescreen(): bool
    {
        return $this->welcomescreen;
    }

    /**
     * @return $this
     */
    public function setCloseWarning(bool $closeWarning): static
    {
        $this->closeWarning = $closeWarning;

        return $this;
    }

    public function getCloseWarning(): bool
    {
        return $this->closeWarning;
    }

    /**
     * @return $this
     */
    public function setMemorizeTabs(bool $memorizeTabs): static
    {
        $this->memorizeTabs = $memorizeTabs;

        return $this;
    }

    public function getMemorizeTabs(): bool
    {
        return $this->memorizeTabs;
    }

    /**
     * @return $this
     */
    public function setAllowDirtyClose(bool $allowDirtyClose): static
    {
        $this->allowDirtyClose = $allowDirtyClose;

        return $this;
    }

    public function getAllowDirtyClose(): bool
    {
        return $this->allowDirtyClose;
    }

    /**
     * @internal
     */
    protected function getOriginalImageStoragePath(): string
    {
        return sprintf('/user-image/user-%s.png', $this->getId());
    }

    /**
     * @internal
     */
    protected function getThumbnailImageStoragePath(): string
    {
        return sprintf('/user-image/user-thumbnail-%s.png', $this->getId());
    }

    public function setImage(?string $path): void
    {
        $storage = Tool\Storage::get('admin');
        $originalFileStoragePath = $this->getOriginalImageStoragePath();
        $thumbFileStoragePath = $this->getThumbnailImageStoragePath();

        if ($storage->fileExists($originalFileStoragePath)) {
            $storage->delete($originalFileStoragePath);
        }

        if ($storage->fileExists($thumbFileStoragePath)) {
            $storage->delete($thumbFileStoragePath);
        }

        if ($path) {
            $handle = fopen($path, 'rb');
            $storage->writeStream($originalFileStoragePath, $handle);
            fclose($handle);
        }
    }

    /**
     * @param int|null $width
     * @param int|null $height
     *
     * @return resource
     */
    public function getImage(?int $width = null, ?int $height = null)
    {
        if (!$width) {
            $width = 46;
        }
        if (!$height) {
            $height = 46;
        }

        $storage = Tool\Storage::get('admin');
        if ($storage->fileExists($this->getOriginalImageStoragePath())) {
            if (!$storage->fileExists($this->getThumbnailImageStoragePath())) {
                $localFile = self::getLocalFileFromStream($storage->readStream($this->getOriginalImageStoragePath()));
                $targetFile = File::getLocalTempFilePath('png');

                $image = \Pimcore\Image::getInstance();
                $image->load($localFile);
                $image->cover($width, $height);
                $image->save($targetFile, 'png');

                $storage->write($this->getThumbnailImageStoragePath(), file_get_contents($targetFile));
            }

            return $storage->readStream($this->getThumbnailImageStoragePath());
        }

        return fopen($this->getFallbackImage(), 'rb');
    }

    /**
     * @return string[]
     */
    public function getContentLanguages(): array
    {
        if (strlen($this->contentLanguages)) {
            return explode(',', $this->contentLanguages);
        }

        return [];
    }

    /**
     * @param string[]|string|null $contentLanguages
     */
    public function setContentLanguages(array|string|null $contentLanguages): void
    {
        if (is_array($contentLanguages)) {
            $contentLanguages = implode(',', $contentLanguages);
        }
        $this->contentLanguages = $contentLanguages;
    }

    public function getActivePerspective(): string
    {
        if (!$this->activePerspective) {
            $this->activePerspective = 'default';
        }

        return $this->activePerspective;
    }

    public function setActivePerspective(?string $activePerspective): void
    {
        $this->activePerspective = $activePerspective;
    }

    /**
     * Returns array of perspectives names related to user and all related roles
     *
     * @return string[]
     */
    private function getMergedPerspectives(): array
    {
        if (null === $this->mergedPerspectives) {
            $this->mergedPerspectives = $this->getPerspectives();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedPerspectives = array_merge($this->mergedPerspectives, $userRole->getPerspectives());
            }
            $this->mergedPerspectives = array_values($this->mergedPerspectives);
            if (!$this->mergedPerspectives) {
                // $perspectives = \Pimcore\Config::getAvailablePerspectives($this);
                $allPerspectives = \Pimcore\Perspective\Config::get();
                $this->mergedPerspectives = [];

                $this->mergedPerspectives = array_keys($allPerspectives);
            }
        }

        return $this->mergedPerspectives;
    }

    /**
     * Returns the first perspective name
     *
     * @internal
     */
    public function getFirstAllowedPerspective(): string
    {
        $perspectives = $this->getMergedPerspectives();
        if (!empty($perspectives)) {
            return $perspectives[0];
        } else {
            // all perspectives are allowed
            $perspectives = \Pimcore\Perspective\Config::getAvailablePerspectives($this);

            return $perspectives[0]['name'];
        }
    }

    /**
     * Returns array of website translation languages for editing related to user and all related roles
     *
     * @return string[]
     */
    private function getMergedWebsiteTranslationLanguagesEdit(): array
    {
        if (null === $this->mergedWebsiteTranslationLanguagesEdit) {
            $this->mergedWebsiteTranslationLanguagesEdit = $this->getWebsiteTranslationLanguagesEdit();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedWebsiteTranslationLanguagesEdit = array_merge($this->mergedWebsiteTranslationLanguagesEdit, $userRole->getWebsiteTranslationLanguagesEdit());
            }
            $this->mergedWebsiteTranslationLanguagesEdit = array_values(array_unique($this->mergedWebsiteTranslationLanguagesEdit));
        }

        return $this->mergedWebsiteTranslationLanguagesEdit;
    }

    /**
     * Returns array of languages allowed for editing. If edit and view languages are empty all languages are allowed.
     * If only edit languages are empty (but view languages not) empty array is returned.
     *
     * @internal
     *
     * @return string[]|null
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations(): ?array
    {
        $mergedWebsiteTranslationLanguagesEdit = $this->getMergedWebsiteTranslationLanguagesEdit();
        if (empty($mergedWebsiteTranslationLanguagesEdit) || $this->isAdmin()) {
            $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
            if (empty($mergedWebsiteTranslationLanguagesView)) {
                return Tool::getValidLanguages();
            }
        }

        return $mergedWebsiteTranslationLanguagesEdit;
    }

    /**
     * Returns array of website translation languages for viewing related to user and all related roles
     *
     * @return string[]
     */
    private function getMergedWebsiteTranslationLanguagesView(): array
    {
        if (null === $this->mergedWebsiteTranslationLanguagesView) {
            $this->mergedWebsiteTranslationLanguagesView = $this->getWebsiteTranslationLanguagesView();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedWebsiteTranslationLanguagesView = array_merge($this->mergedWebsiteTranslationLanguagesView, $userRole->getWebsiteTranslationLanguagesView());
            }

            $this->mergedWebsiteTranslationLanguagesView = array_values(array_unique($this->mergedWebsiteTranslationLanguagesView));
        }

        return $this->mergedWebsiteTranslationLanguagesView;
    }

    /**
     * Returns array of languages allowed for viewing. If view languages are empty all languages are allowed.
     *
     * @internal
     *
     * @return string[]|null
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations(): ?array
    {
        $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
        if (empty($mergedWebsiteTranslationLanguagesView) || $this->isAdmin()) {
            return Tool::getValidLanguages();
        }

        return $mergedWebsiteTranslationLanguagesView;
    }

    public function getLastLogin(): int
    {
        return $this->lastLogin;
    }

    /**
     * @return $this
     */
    public function setLastLogin(int $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @internal
     *
     * @return string
     */
    public static function getDefaultKeyBindings(): string
    {
        $userConfig = \Pimcore\Config::getSystemConfiguration('user');
        // make sure the default key binding node is in the config
        if (is_array($userConfig) && array_key_exists(self::DEFAULT_KEY_BINDINGS, $userConfig)) {
            $defaultKeyBindingsConfig = $userConfig[self::DEFAULT_KEY_BINDINGS];
            $defaultKeyBindings = [];
            if (!empty($defaultKeyBindingsConfig)) {
                foreach ($defaultKeyBindingsConfig as $keys) {
                    $defaultKeyBinding = [];
                    // we do not check if the keys are empty because key is required
                    foreach ($keys as $index => $value) {
                        if ($index === 'key') {
                            $value = ord($value);
                        }
                        $defaultKeyBinding[$index] = $value;
                    }
                    $defaultKeyBindings[] = $defaultKeyBinding;
                }
            }
        }

        if (!empty($defaultKeyBindings)) {
            return json_encode($defaultKeyBindings);
        }

        // keep for legacy reasons

        $bindings = [
            [
                'action' => 'save',
                'key' => ord('S'),
                'ctrl' => true,
            ],
            [
                'action' => 'publish',
                'key' => ord('P'),
                'ctrl' => true,
                'shift' => true,
            ],
            [
                'action' => 'unpublish',
                'key' => ord('U'),
                'ctrl' => true,
                'shift' => true,
            ],
            [
                'action' => 'rename',
                'key' => ord('R'),
                'alt' => true,
                'shift' => true,
            ],
            [
                'action' => 'refresh',
                'key' => 116,
            ],
            [
                'action' => 'openAsset',
                'key' => ord('A'),
                'ctrl' => true,
                'shift' => true,
            ],
            [
                'action' => 'openObject',
                'key' => ord('O'),
                'ctrl' => true,
                'shift' => true,
            ],
            [
                'action' => 'openDocument',
                'key' => ord('D'),
                'ctrl' => true,
                'shift' => true,
            ],
            [
                'action' => 'openClassEditor',
                'key' => ord('C'),
                'ctrl' => true,
                'shift' => true,

            ],
            [
                'action' => 'openInTree',
                'key' => ord('L'),
                'ctrl' => true,
                'shift' => true,

            ],
            [
                'action' => 'showMetaInfo',
                'key' => ord('I'),
                'alt' => true,
            ],
            [
                'action' => 'searchDocument',
                'key' => ord('W'),
                'alt' => true,
            ],
            [
                'action' => 'searchAsset',
                'key' => ord('A'),
                'alt' => true,
            ],
            [
                'action' => 'searchObject',
                'key' => ord('O'),
                'alt' => true,
            ],
            [
                'action' => 'showElementHistory',
                'key' => ord('H'),
                'alt' => true,
            ],
            [
                'action' => 'closeAllTabs',
                'key' => ord('T'),
                'alt' => true,
            ],
            [
                'action' => 'searchAndReplaceAssignments',
                'key' => ord('S'),
                'alt' => true,
            ],
            [
                'action' => 'redirects',
                'key' => ord('R'),
                'ctrl' => false,
                'alt' => true,
            ],
            [
                'action' => 'sharedTranslations',
                'key' => ord('T'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'recycleBin',
                'key' => ord('R'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'notesEvents',
                'key' => ord('N'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'applicationLogger',
                'key' => ord('L'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'tagManager',
                    'key' => ord('H'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'seoDocumentEditor',
                    'key' => ord('S'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'robots',
                    'key' => ord('J'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'httpErrorLog',
                    'key' => ord('O'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'tagConfiguration',
                'key' => ord('N'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'users',
                'key' => ord('U'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'roles',
                'key' => ord('P'),
                'ctrl' => true,
                'alt' => true,
            ],
            [
                'action' => 'clearAllCaches',
                'key' => ord('Q'),
                'ctrl' => false,
                'alt' => true,
            ],
            [
                'action' => 'clearDataCache',
                'key' => ord('C'),
                'ctrl' => false,
                'alt' => true,
            ],
            [
                'action' => 'quickSearch',
                'key' => ord('F'),
                'ctrl' => true,
                'shift' => true,
            ],
        ];

        return json_encode(self::strictKeybinds($bindings));
    }

    public function getKeyBindings(): string
    {
        return $this->keyBindings ?: self::getDefaultKeyBindings();
    }

    /**
     * @param list<array{action: string, key: int, alt?: bool, ctrl?: bool, shift?: bool}> $bindings
     *
     * @return list<array{action: string, key: int, alt: bool, ctrl: bool, shift: bool}>
     */
    public static function strictKeybinds(array $bindings): array
    {
        foreach ($bindings as $ind => $binding) {
            $bindings[$ind]['ctrl'] ??= false;
            $bindings[$ind]['alt'] ??= false;
            $bindings[$ind]['shift'] ??= false;
        }

        return $bindings;
    }

    /**
     * @param string $keyBindings
     */
    public function setKeyBindings(string $keyBindings): void
    {
        $this->keyBindings = $keyBindings;
    }

    public function getTwoFactorAuthentication(string $key = null): mixed
    {
        if ($this->twoFactorAuthentication === null) {
            // set defaults if no data is present
            $this->twoFactorAuthentication = [
                'required' => false,
                'enabled' => false,
                'secret' => '',
                'type' => '',
            ];
        }

        if ($key) {
            return $this->twoFactorAuthentication[$key] ?? null;
        }

        return $this->twoFactorAuthentication;
    }

    /**
     * You can either pass an array for setting the entire 2fa settings, or a key and a value as the second argument
     *
     * @param array<string, mixed>|string $key
     */
    public function setTwoFactorAuthentication(array|string $key, mixed $value = null): void
    {
        if (is_string($key) && $value === null && strlen($key) > 3) {
            $this->twoFactorAuthentication = json_decode($key, true);
        } elseif (is_array($key)) {
            $this->twoFactorAuthentication = $key;
        } else {
            if ($this->twoFactorAuthentication === null) {
                // load defaults
                $this->getTwoFactorAuthentication();
            }

            $this->twoFactorAuthentication[$key] = $value;
        }
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    public function hasImage(): bool
    {
        return Tool\Storage::get('admin')->fileExists($this->getOriginalImageStoragePath());
    }

    /**
     * @internal
     */
    protected function getFallbackImage(): string
    {
        return PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/avatar.png';
    }
}
