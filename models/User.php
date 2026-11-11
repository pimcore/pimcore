<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model;

use Exception;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\User\Exception\DuplicateUserNameException;
use Pimcore\Model\User\Exception\InvalidServiceUserException;
use Pimcore\Model\User\Role;
use Pimcore\Tool;

use function array_key_exists;
use function array_keys;
use function bin2hex;
use function is_array;
use function is_string;
use function random_bytes;
use function sprintf;
use function strcasecmp;

/**
 * A user of Pimcore: a person (type `user`) or a service user (type `serviceuser`, see isServiceUser()), a
 * machine identity for integrations, agents and pipelines. Both are rows of the `users` table and share this
 * class; a service user is authorized like a person (permissions, roles, workspaces, class and document type
 * restrictions) but never logs in interactively: it keeps an unusable random password, is never an administrator
 * and has no two-factor authentication. User::getByName() and \Pimcore\Model\User\Listing return persons only;
 * service users are found with User::getServiceUserByName() and \Pimcore\Model\User\ServiceUser\Listing, and
 * User::getById() returns either kind. Names are unique across persons and service users, and the type of a
 * saved user never changes.
 *
 * @method User\Dao getDao()
 */
final class User extends User\UserRole implements UserInterface
{
    use TemporaryFileHelperTrait;

    private const TYPE_SERVICE_USER = 'serviceuser';

    private const TWO_FACTOR_DEFAULTS = [
        'required' => false,
        'enabled' => false,
        'secret' => '',
        'type' => '',
    ];

    private const UNUSABLE_PASSWORD_PREFIX = '!serviceuser-';

    protected string $type = 'user';

    protected ?string $password = null;

    protected ?string $passwordRecoveryToken = null;

    private ?int $lastPasswordReset = null;

    protected ?string $firstname = null;

    protected ?string $lastname = null;

    protected ?string $email = null;

    protected string $language = 'en';

    protected ?string $datetimeLocale = null;

    protected string $theme = 'default';

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

    protected ?int $lastLogin = null;

    protected ?string $keyBindings = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $twoFactorAuthentication = null;

    /**
     * OIDC Provider from pimcore/openid-connect
     */
    protected ?string $provider = null;

    /**
     * Whether this is a service user (type `serviceuser`): a machine identity that never logs in interactively.
     */
    public function isServiceUser(): bool
    {
        return $this->type === self::TYPE_SERVICE_USER;
    }

    /**
     * An unsaved service user; set its values and save() it.
     */
    public static function newServiceUser(): self
    {
        $serviceUser = new self();
        $serviceUser->setType(self::TYPE_SERVICE_USER);

        return $serviceUser;
    }

    /**
     * Creates and saves a service user with the values, like create() does for a person. A `type` in the values is
     * ignored, and a `password` is replaced by the unusable value save() stores anyway.
     *
     * @param array<string, mixed> $values
     *
     * @throws DuplicateUserNameException if a person or service user already has the name
     * @throws InvalidServiceUserException if the values give it administrator rights or two-factor data
     * @throws Exception if the values carry an `id`
     */
    public static function createServiceUser(array $values = []): self
    {
        $values = self::withoutTypeKeys($values);
        self::checkCreateData($values);

        $serviceUser = self::newServiceUser();
        $serviceUser->setValues($values);
        // no value may have changed the kind on the way; save() then refuses administrator rights and two-factor data
        $serviceUser->setType(self::TYPE_SERVICE_USER);
        $serviceUser->save();

        return $serviceUser;
    }

    /**
     * The service user with this name, or null. getByName() finds persons only.
     */
    public static function getServiceUserByName(string $name): ?self
    {
        $serviceUser = self::newServiceUser();

        try {
            $serviceUser->getDao()->getByName($name);
        } catch (NotFoundException) {
            return null;
        }

        return $serviceUser;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function withoutTypeKeys(array $values): array
    {
        foreach (array_keys($values) as $key) {
            if (is_string($key) && strcasecmp($key, 'type') === 0) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Applies the type first, so the other values are validated for the right kind of user, and keeps the
     * stored password of a service user as it is: a loaded service user passes the same validity check
     * (active, saved, with a password) as a person, and save() replaces the value with a new unusable one anyway.
     */
    public function setValues(array $data = [], bool $ignoreEmptyValues = false): static
    {
        // setters are dispatched case-insensitively (setValue()), so `Type` or `TYPE` set the type as well
        foreach ($data as $key => $value) {
            if (is_string($key) && strcasecmp($key, 'type') === 0) {
                if (is_string($value)) {
                    $this->setType($value);
                }
                unset($data[$key]);
            }
        }

        if ($this->isServiceUser() && array_key_exists('password', $data)) {
            $password = $data['password'];
            unset($data['password']);
            $this->password = is_string($password) && $password !== '' ? $password : null;
        }

        return parent::setValues($data, $ignoreEmptyValues);
    }

    /**
     * @return $this
     *
     * @throws DuplicateUserNameException if a person or service user of the other kind already has the name
     * @throws InvalidServiceUserException if the type of a saved user is changed, or a service user has
     *     administrator rights or two-factor data
     * @throws Exception
     */
    public function save(array $parameters = []): static
    {
        if ($this->getId() !== null) {
            $storedType = $this->getDao()->getStoredType();
            if ($storedType !== null && $storedType !== $this->getType()) {
                throw new InvalidServiceUserException(
                    'The type of a saved user never changes: a person does not become a service user, nor the reverse.'
                );
            }
        }

        if ($this->isServiceUser()) {
            if ($this->admin || $this->hasTwoFactorData()) {
                throw new InvalidServiceUserException(
                    'A service user cannot have administrator rights or two-factor authentication.'
                );
            }

            // never a usable password: not a password hash, so no password ever verifies against it
            $this->password = self::UNUSABLE_PASSWORD_PREFIX . bin2hex(random_bytes(32));
        }

        $name = $this->getName();
        if ($name !== null && $this->getDao()->isNameTakenByOtherUserType()) {
            throw new DuplicateUserNameException(
                sprintf('The name "%s" is already used by another user or service user.', $name)
            );
        }

        return parent::save($parameters);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * A service user never gets a password from a caller: save() stores an unusable value instead, and passing
     * null keeps it.
     *
     * @return $this
     *
     * @throws InvalidServiceUserException when a service user is given a password
     */
    public function setPassword(?string $password): static
    {
        if ($this->isServiceUser()) {
            if ($password !== null) {
                throw new InvalidServiceUserException('A service user cannot have a password.');
            }

            return $this;
        }

        if (strlen((string) $password) > 4) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * @internal
     */
    public function getPasswordRecoveryToken(): ?string
    {
        return $this->passwordRecoveryToken;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function setPasswordRecoveryToken(?string $passwordRecoveryToken): static
    {
        $this->passwordRecoveryToken = $passwordRecoveryToken;

        return $this;
    }

    /**
     * Alias for getName()
     *
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
     */
    public function isAdmin(): bool
    {
        return $this->getAdmin();
    }

    /**
     * A service user is never an administrator.
     */
    public function getAdmin(): bool
    {
        return $this->admin && !$this->isServiceUser();
    }

    /**
     * @return $this
     *
     * @throws InvalidServiceUserException when a service user is given administrator rights
     */
    public function setAdmin(bool $admin): static
    {
        if ($admin && $this->isServiceUser()) {
            throw new InvalidServiceUserException('A service user cannot be an administrator.');
        }

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
        $this->welcomescreen = $welcomescreen;

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
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
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
                $originalImageStream = $storage->readStream($this->getOriginalImageStoragePath());
                $localFile = self::getLocalFileFromStream($originalImageStream);
                @fclose($originalImageStream);
                $targetFile = File::getLocalTempFilePath('png');

                $image = \Pimcore\Image::getInstance();
                if ($image->load($localFile)) {
                    $image->cover($width, $height);
                    $image->save($targetFile, 'png');
                    $storage->write($this->getThumbnailImageStoragePath(), file_get_contents($targetFile));
                }
            }

            if ($storage->fileExists($this->getThumbnailImageStoragePath())) {
                return $storage->readStream($this->getThumbnailImageStoragePath());
            }
        }

        return fopen($this->getFallbackImage(), 'rb');
    }

    /**
     * @return string[]
     */
    public function getContentLanguages(): array
    {
        if (is_string($this->contentLanguages) && strlen($this->contentLanguages)) {
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

        return $perspectives[0] ?? 'default';
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
     * @return string[]
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations(): array
    {
        $mergedWebsiteTranslationLanguagesEdit = $this->getMergedWebsiteTranslationLanguagesEdit();
        if (
            (!$mergedWebsiteTranslationLanguagesEdit && !$this->getMergedWebsiteTranslationLanguagesView()) ||
            $this->isAdmin()
        ) {
            return Tool::getValidLanguages();
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
     * @return string[]
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations(): array
    {
        $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
        if (empty($mergedWebsiteTranslationLanguagesView) || $this->isAdmin()) {
            return Tool::getValidLanguages();
        }

        return $mergedWebsiteTranslationLanguagesView;
    }

    public function getLastLogin(): ?int
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
     * @return $this
     */
    public function setLastPasswordReset(int $lastPasswordReset): static
    {
        $this->lastPasswordReset = $lastPasswordReset;

        return $this;
    }

    public function getKeyBindings(): ?string
    {
        return $this->keyBindings;
    }

    public function setKeyBindings(string $keyBindings): void
    {
        $this->keyBindings = $keyBindings;
    }

    /**
     * A service user has no two-factor authentication; for it this always returns the disabled defaults.
     */
    public function getTwoFactorAuthentication(?string $key = null): mixed
    {
        if ($this->isServiceUser()) {
            if ($key) {
                return self::TWO_FACTOR_DEFAULTS[$key] ?? null;
            }

            return self::TWO_FACTOR_DEFAULTS;
        }

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
     * You can either pass an array for setting the entire 2fa settings, or a key and a value as the second argument.
     * A service user accepts only data that leaves two-factor authentication disabled and without a secret.
     *
     * @param array<string, mixed>|string $key
     *
     * @throws InvalidServiceUserException when the data enables two-factor authentication for a service user
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
                $this->twoFactorAuthentication = self::TWO_FACTOR_DEFAULTS;
            }

            $this->twoFactorAuthentication[$key] = $value;
        }

        if ($this->isServiceUser() && $this->hasTwoFactorData()) {
            $this->twoFactorAuthentication = null;

            throw new InvalidServiceUserException('A service user cannot use two-factor authentication.');
        }
    }

    private function hasTwoFactorData(): bool
    {
        $data = $this->twoFactorAuthentication;
        if (!is_array($data)) {
            return false;
        }

        // the flags are read by PHP truthiness (see \Pimcore\Security\User\User::isGoogleAuthenticatorEnabled()),
        // so any truthy value (1, '1', 'true', even 'false') enables two-factor authentication just like true does
        return (bool) ($data['enabled'] ?? false)
            || (bool) ($data['required'] ?? false)
            || (string) ($data['secret'] ?? '') !== ''
            || (string) ($data['type'] ?? '') !== '';
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

    public function getDatetimeLocale(): ?string
    {
        return $this->datetimeLocale;
    }

    public function setDatetimeLocale(?string $datetimeLocale): static
    {
        $this->datetimeLocale = $datetimeLocale;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getLastPasswordReset(): ?int
    {
        return $this->lastPasswordReset;
    }
}
