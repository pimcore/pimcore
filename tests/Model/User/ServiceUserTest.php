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

namespace Pimcore\Tests\Model\User;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\Element\Editlock;
use Pimcore\Model\Notification;
use Pimcore\Model\Translation;
use Pimcore\Model\User;
use Pimcore\Model\User\AbstractUser;
use Pimcore\Model\User\Exception\DuplicateUserNameException;
use Pimcore\Model\User\Exception\InvalidServiceUserException;
use Pimcore\Model\User\Listing as UserListing;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Service;
use Pimcore\Model\User\ServiceUser;
use Pimcore\Model\User\ServiceUser\Listing as ServiceUserListing;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Authentication;

class ServiceUserTest extends ModelTestCase
{
    private const NAME_PREFIX = 'svc-test-';

    /**
     * @var AbstractUser[]
     */
    private array $createdEntities = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->createdEntities) as $entity) {
            if ($entity->getId() && AbstractUser::getById($entity->getId())) {
                $entity->delete();
            }
        }
        $this->createdEntities = [];

        parent::tearDown();
    }

    public function testServiceTypeMapsToServiceUserClass(): void
    {
        $this->assertSame(ServiceUser::class, Service::getClassNameForType('serviceuser'));
    }

    public function testNewServiceUserHasServiceType(): void
    {
        $serviceUser = new ServiceUser();

        $this->assertSame('serviceuser', $serviceUser->getType());
        $this->assertFalse($serviceUser->isAdmin());
        $this->assertNull($serviceUser->getPassword());
    }

    public function testGetByIdReturnsServiceUserForServiceRow(): void
    {
        $serviceUser = $this->createServiceUser('load');

        $this->clearUserCache($serviceUser->getId());
        $this->assertInstanceOf(ServiceUser::class, User::getById($serviceUser->getId()));

        $this->clearUserCache($serviceUser->getId());
        $this->assertInstanceOf(ServiceUser::class, ServiceUser::getById($serviceUser->getId()));

        $this->clearUserCache($serviceUser->getId());
        $this->assertInstanceOf(ServiceUser::class, AbstractUser::getById($serviceUser->getId()));
    }

    public function testServiceUserGetByIdDoesNotReturnHuman(): void
    {
        $human = $this->createHuman('human-by-id');

        $this->clearUserCache($human->getId());
        $this->assertNull(ServiceUser::getById($human->getId()));
    }

    public function testUserGetByNameDoesNotFindServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('by-name');

        $this->assertNull(User::getByName($serviceUser->getName()));
        $this->assertInstanceOf(ServiceUser::class, ServiceUser::getByName($serviceUser->getName()));
    }

    public function testUserGetByNameStillFindsHuman(): void
    {
        $human = $this->createHuman('human-by-name');

        $found = User::getByName($human->getName());

        $this->assertInstanceOf(User::class, $found);
        $this->assertNotInstanceOf(ServiceUser::class, $found);
    }

    public function testServiceUserIsPersistedWithUnusablePasswordAndNoAdmin(): void
    {
        $serviceUser = $this->createServiceUser('persisted');

        $row = Db::get()->fetchAssociative(
            'SELECT `type`, `password`, `admin` FROM users WHERE id = ?',
            [$serviceUser->getId()]
        );

        $this->assertSame('serviceuser', $row['type']);
        $this->assertIsString($row['password']);
        $this->assertNotSame('', $row['password']);
        $this->assertNull(password_get_info($row['password'])['algo'], 'The stored value must not be a password hash');
        $this->assertSame(0, (int) $row['admin']);
    }

    public function testSavedServiceUserIsValidLikeAUserWithAPassword(): void
    {
        $serviceUser = $this->createServiceUser('valid');

        $this->assertTrue(Authentication::isValidUser($serviceUser));

        $this->clearUserCache($serviceUser->getId());
        $this->assertTrue(Authentication::isValidUser(ServiceUser::getById($serviceUser->getId())));
    }

    public function testInactiveServiceUserIsNotValid(): void
    {
        $serviceUser = $this->createServiceUser('inactive');
        $serviceUser->setActive(false);
        $serviceUser->save();

        $this->assertFalse(Authentication::isValidUser($serviceUser));
    }

    public function testNoPasswordVerifiesAgainstAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('verify');

        $this->clearUserCache($serviceUser->getId());
        $loaded = ServiceUser::getById($serviceUser->getId());

        $this->assertFalse(Authentication::verifyPassword($loaded, ''));
        $this->assertFalse(Authentication::verifyPassword($loaded, 'a-long-test-password'));
        $this->assertFalse(Authentication::verifyPassword($loaded, (string) $loaded->getPassword()));
    }

    public function testPasswordGivenOnCreateIsReplacedByAnUnusableOne(): void
    {
        $hash = Authentication::getPasswordHash(self::NAME_PREFIX . 'create-pw', 'a-long-test-password');
        $serviceUser = ServiceUser::create(['name' => self::NAME_PREFIX . 'create-pw', 'password' => $hash]);
        $this->createdEntities[] = $serviceUser;

        $this->assertNotSame($hash, $serviceUser->getPassword());
        $this->assertFalse(Authentication::verifyPassword($serviceUser, 'a-long-test-password'));
    }

    public function testVersionOfAnElementSavedByAServiceUserKeepsTheServiceUserAsAuthor(): void
    {
        $serviceUser = $this->createServiceUser('version-author');

        $document = TestHelper::createEmptyDocumentPage('svc-version-', false);
        $document->setUserModification($serviceUser->getId());
        $document->save();

        try {
            $version = $document->getLatestVersion(null, true);

            $this->assertInstanceOf(Version::class, $version);
            $this->assertSame($serviceUser->getId(), $version->getUserId());
            $this->assertInstanceOf(ServiceUser::class, $version->getUser());
            $this->assertSame($serviceUser->getId(), $version->getUser()->getId());
        } finally {
            $document->delete();
        }
    }

    public function testSetPasswordThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (new ServiceUser())->setPassword('some-password-hash');
    }

    public function testSetPasswordNullIsAccepted(): void
    {
        $serviceUser = (new ServiceUser())->setPassword(null);

        $this->assertNull($serviceUser->getPassword());
    }

    public function testSetAdminTrueThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (new ServiceUser())->setAdmin(true);
    }

    public function testSetAdminFalseIsAccepted(): void
    {
        $this->assertFalse((new ServiceUser())->setAdmin(false)->isAdmin());
    }

    public function testEnablingTwoFactorAuthenticationThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (new ServiceUser())->setTwoFactorAuthentication('enabled', true);
    }

    public function testTwoFactorAuthenticationIsDisabled(): void
    {
        $serviceUser = new ServiceUser();

        $this->assertFalse($serviceUser->getTwoFactorAuthentication('enabled'));
        $this->assertFalse($serviceUser->getTwoFactorAuthentication('required'));
        $this->assertSame('', $serviceUser->getTwoFactorAuthentication('secret'));
    }

    public function testSaveRejectsTypeChange(): void
    {
        $serviceUser = $this->createServiceUser('type-change');
        $serviceUser->setType('user');

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->save();
    }

    public function testHumanCannotBeSavedAsServiceType(): void
    {
        $human = new User();
        $human->setName(self::NAME_PREFIX . 'disguised');
        $human->setPassword('some-password-hash');
        $human->setType('serviceuser');

        try {
            $this->expectException(InvalidServiceUserException::class);
            $human->save();
        } finally {
            $this->assertNull(User::getByName(self::NAME_PREFIX . 'disguised'));
            $this->assertNull(ServiceUser::getByName(self::NAME_PREFIX . 'disguised'));
        }
    }

    public function testServiceUserNameTakenByHumanIsRejected(): void
    {
        $human = $this->createHuman('shared-1');

        $serviceUser = new ServiceUser();
        $serviceUser->setName($human->getName());

        $this->expectException(DuplicateUserNameException::class);
        $serviceUser->save();
    }

    public function testHumanNameTakenByServiceUserIsRejected(): void
    {
        $serviceUser = $this->createServiceUser('shared-2');

        $human = new User();
        $human->setName($serviceUser->getName());

        $this->expectException(DuplicateUserNameException::class);
        $human->save();
    }

    public function testRenamingHumanToServiceUserNameIsRejected(): void
    {
        $serviceUser = $this->createServiceUser('shared-3');
        $human = $this->createHuman('rename-me');
        $human->setName($serviceUser->getName());

        $this->expectException(DuplicateUserNameException::class);
        $human->save();
    }

    public function testResavingServiceUserKeepsWorking(): void
    {
        $serviceUser = $this->createServiceUser('resave');
        $serviceUser->setEmail('integration-owner@example.com');
        $serviceUser->save();

        $this->clearUserCache($serviceUser->getId());
        $this->assertSame('integration-owner@example.com', ServiceUser::getById($serviceUser->getId())->getEmail());
    }

    public function testRoleNameMayEqualServiceUserName(): void
    {
        $serviceUser = $this->createServiceUser('role-twin');

        $role = new Role();
        $role->setName($serviceUser->getName());
        $role->save();
        $this->createdEntities[] = $role;

        $this->assertNotNull($role->getId());
    }

    public function testUserListingExcludesServiceUsers(): void
    {
        $serviceUser = $this->createServiceUser('listed');
        $human = $this->createHuman('listed-human');

        $ids = $this->listingIds(new UserListing());

        $this->assertContains($human->getId(), $ids);
        $this->assertNotContains($serviceUser->getId(), $ids);
    }

    public function testServiceUserListingContainsOnlyServiceUsers(): void
    {
        $serviceUser = $this->createServiceUser('listed-2');
        $human = $this->createHuman('listed-human-2');

        $listing = new ServiceUserListing();
        $ids = $this->listingIds($listing);

        $this->assertContains($serviceUser->getId(), $ids);
        $this->assertNotContains($human->getId(), $ids);
        foreach ($listing->getServiceUsers() as $item) {
            $this->assertInstanceOf(ServiceUser::class, $item);
        }
    }

    public function testDeletingRoleRemovesItFromServiceUsers(): void
    {
        $role = new Role();
        $role->setName(self::NAME_PREFIX . 'role-cleanup');
        $role->save();
        $this->createdEntities[] = $role;

        $serviceUser = new ServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . 'role-holder');
        $serviceUser->setRoles([$role->getId()]);
        $serviceUser->save();
        $this->createdEntities[] = $serviceUser;

        $role->delete();

        $this->clearUserCache($serviceUser->getId());
        $this->assertSame([], ServiceUser::getById($serviceUser->getId())->getRoles());
    }

    public function testAuthenticateTokenRefusesServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('token');
        $token = Authentication::generateTokenByUser($serviceUser);

        $this->assertNull(Authentication::authenticateToken($token));
    }

    public function testAuthenticateTokenAcceptsHuman(): void
    {
        $human = $this->createHuman('token-human');
        $token = Authentication::generateTokenByUser($human);

        $authenticated = Authentication::authenticateToken($token);

        $this->assertInstanceOf(User::class, $authenticated);
        $this->assertSame($human->getId(), $authenticated->getId());
    }

    public function testVersionAuthorMayBeAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('version-author');

        $version = new Version();
        $version->setUserId($serviceUser->getId());

        $this->assertSame($serviceUser->getId(), $version->getUserId());
        $this->assertInstanceOf(ServiceUser::class, $version->getUser());
    }

    public function testEditlockHolderMayBeAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('lock-holder');

        $lock = new Editlock();
        $lock->setUserId($serviceUser->getId());

        $this->assertInstanceOf(ServiceUser::class, $lock->getUser());
    }

    public function testNotificationSenderMayBeAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('notifier');
        $human = $this->createHuman('notified');

        $notification = new Notification();
        $notification->setType('info');
        $notification->setTitle('from a service user');
        $notification->setMessage('sent by an integration');
        $notification->setSender($serviceUser);
        $notification->setRecipient($human);
        $notification->save();

        try {
            $loaded = Notification::getById($notification->getId());

            $this->assertNotNull($loaded);
            $this->assertInstanceOf(ServiceUser::class, $loaded->getSender());
            $this->assertSame($serviceUser->getId(), $loaded->getSender()->getId());
            $this->assertSame($human->getId(), $loaded->getRecipient()?->getId());
        } finally {
            $notification->delete();
        }
    }

    public function testWebsiteTranslationLanguageRestrictionAppliesToAServiceUser(): void
    {
        $languages = Translation::getValidLanguages(Translation::DOMAIN_DEFAULT);
        if (count($languages) < 2) {
            $this->markTestSkipped('needs at least two valid languages');
        }
        [$allowed, $forbidden] = $languages;

        $serviceUser = $this->createServiceUser('translator');
        $serviceUser->setWebsiteTranslationLanguagesEdit([$allowed]);
        $serviceUser->save();
        $this->clearUserCache($serviceUser->getId());

        $key = self::NAME_PREFIX . 'translation-' . uniqid();
        $translation = new Translation();
        $translation->setKey($key);
        $translation->setDomain(Translation::DOMAIN_DEFAULT);
        $translation->setUserModification($serviceUser->getId());
        $translation->addTranslation($allowed, 'allowed');
        $translation->addTranslation($forbidden, 'forbidden');
        $translation->save();

        try {
            $this->clearTranslationCache($key);
            $stored = Translation::getByKey($key, Translation::DOMAIN_DEFAULT);

            $this->assertNotNull($stored);
            $this->assertSame('allowed', $stored->getTranslations()[$allowed] ?? null);
            $this->assertArrayNotHasKey($forbidden, $stored->getTranslations());
        } finally {
            $translation->delete();
        }
    }

    private function clearTranslationCache(string $key): void
    {
        $cacheKey = 'translation_' . $key . '_' . Translation::DOMAIN_DEFAULT;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }

    private function createServiceUser(string $suffix): ServiceUser
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . $suffix);
        $serviceUser->save();
        $this->createdEntities[] = $serviceUser;

        return $serviceUser;
    }

    private function createHuman(string $suffix): User
    {
        $human = new User();
        $human->setName(self::NAME_PREFIX . $suffix);
        $human->setPassword(Authentication::getPasswordHash(self::NAME_PREFIX . $suffix, 'a-long-test-password'));
        $human->save();
        $this->createdEntities[] = $human;

        return $human;
    }

    /**
     * @return int[]
     */
    private function listingIds(UserListing|ServiceUserListing $listing): array
    {
        $listing->setCondition('name LIKE ?', [self::NAME_PREFIX . '%']);

        return array_map(static fn (AbstractUser $user): ?int => $user->getId(), $listing->load());
    }

    private function clearUserCache(?int $id): void
    {
        $cacheKey = 'user_' . $id;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }
}
