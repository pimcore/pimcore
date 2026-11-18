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

use Exception;
use Pimcore;
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
use Pimcore\Model\User\ServiceUser\Listing as ServiceUserListing;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;
use Pimcore\Model\Version;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Security\User\UserProvider;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Authentication;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

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

    public function testServiceTypeMapsToTheUserClass(): void
    {
        $this->assertSame(User::class, Service::getClassNameForType('serviceuser'));
    }

    public function testNewServiceUserHasServiceType(): void
    {
        $serviceUser = User::newServiceUser();

        $this->assertSame('serviceuser', $serviceUser->getType());
        $this->assertTrue($serviceUser->isServiceUser());
        $this->assertFalse((new User())->isServiceUser());
        $this->assertFalse($serviceUser->isAdmin());
        $this->assertNull($serviceUser->getPassword());
    }

    public function testGetByIdReturnsAServiceUserForAServiceRow(): void
    {
        $serviceUser = $this->createServiceUser('load');

        $this->clearUserCache($serviceUser->getId());
        $this->assertIsServiceUser(User::getById($serviceUser->getId()));

        $this->clearUserCache($serviceUser->getId());
        $this->assertIsServiceUser(AbstractUser::getById($serviceUser->getId()));
    }

    public function testGetByIdReturnsAPersonForAUserRow(): void
    {
        $human = $this->createHuman('human-by-id');

        $this->clearUserCache($human->getId());
        $loaded = User::getById($human->getId());

        $this->assertInstanceOf(User::class, $loaded);
        $this->assertFalse($loaded->isServiceUser());
    }

    public function testUserGetByNameDoesNotFindServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('by-name');

        $this->assertNull(User::getByName($serviceUser->getName()));
        $this->assertIsServiceUser(User::getServiceUserByName($serviceUser->getName()));
    }

    public function testUserGetByNameStillFindsHuman(): void
    {
        $human = $this->createHuman('human-by-name');

        $found = User::getByName($human->getName());

        $this->assertInstanceOf(User::class, $found);
        $this->assertFalse($found->isServiceUser());
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
        $this->assertTrue(Authentication::isValidUser(User::getById($serviceUser->getId())));
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
        $loaded = User::getById($serviceUser->getId());

        $this->assertFalse(Authentication::verifyPassword($loaded, ''));
        $this->assertFalse(Authentication::verifyPassword($loaded, 'a-long-test-password'));
        $this->assertFalse(Authentication::verifyPassword($loaded, (string) $loaded->getPassword()));
    }

    public function testPasswordGivenOnCreateIsReplacedByAnUnusableOne(): void
    {
        $hash = Authentication::getPasswordHash(self::NAME_PREFIX . 'create-pw', 'a-long-test-password');
        $serviceUser = User::createServiceUser(['name' => self::NAME_PREFIX . 'create-pw', 'password' => $hash]);
        $this->createdEntities[] = $serviceUser;

        $this->assertNotSame($hash, $serviceUser->getPassword());
        $this->assertFalse(Authentication::verifyPassword($serviceUser, 'a-long-test-password'));
    }

    public function testCreateServiceUserIgnoresATypeInTheValues(): void
    {
        $serviceUser = User::createServiceUser(['name' => self::NAME_PREFIX . 'create-type', 'type' => 'user']);
        $this->createdEntities[] = $serviceUser;

        $this->assertTrue($serviceUser->isServiceUser());
        $this->clearUserCache($serviceUser->getId());
        $this->assertTrue(User::getById($serviceUser->getId())?->isServiceUser());
    }

    /**
     * Setters are dispatched case-insensitively, so a `Type` key must not turn the new user into a person on the way.
     *
     * @dataProvider typeKeyProvider
     */
    public function testCreateServiceUserNeverCreatesAnAdministratorThroughATypeKey(string $typeKey): void
    {
        $name = self::NAME_PREFIX . 'create-' . strtolower($typeKey);

        try {
            $created = User::createServiceUser(['name' => $name, $typeKey => 'user', 'admin' => true]);
            $this->createdEntities[] = $created;
            $this->fail('a service user with administrator rights was saved');
        } catch (InvalidServiceUserException) {
            $this->assertNull(User::getByName($name), 'no person may have been saved');
            $this->assertNull(User::getServiceUserByName($name));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function typeKeyProvider(): array
    {
        return ['lower' => ['type'], 'capitalised' => ['Type'], 'upper' => ['TYPE']];
    }

    public function testCreateServiceUserWithACapitalisedTypeKeyStaysAServiceUser(): void
    {
        $serviceUser = User::createServiceUser(['name' => self::NAME_PREFIX . 'create-cap', 'Type' => 'user']);
        $this->createdEntities[] = $serviceUser;

        $this->clearUserCache($serviceUser->getId());
        $this->assertTrue(User::getById($serviceUser->getId())?->isServiceUser());
    }

    public function testASessionCarryingAServiceUserIsNotAuthenticated(): void
    {
        $serviceUser = $this->createServiceUser('session-auth');

        $session = new Session(new MockArraySessionStorage());
        $session->set(
            '_security_pimcore_admin',
            serialize(new UsernamePasswordToken(new SecurityUser($serviceUser), 'pimcore_admin', ['ROLE_PIMCORE_USER']))
        );
        $request = new Request();
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'session-id');

        $this->assertNull(Authentication::authenticateSession($request));
    }

    public function testCreateServiceUserRefusesAnId(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('including `id` key');

        User::createServiceUser(['id' => 7, 'name' => self::NAME_PREFIX . 'create-id']);
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
            $this->assertIsServiceUser($version->getUser());
            $this->assertSame($serviceUser->getId(), $version->getUser()->getId());
        } finally {
            $document->delete();
        }
    }

    public function testSetPasswordThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (User::newServiceUser())->setPassword('some-password-hash');
    }

    public function testSetPasswordNullIsAccepted(): void
    {
        $serviceUser = (User::newServiceUser())->setPassword(null);

        $this->assertNull($serviceUser->getPassword());
    }

    public function testSetAdminTrueThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (User::newServiceUser())->setAdmin(true);
    }

    public function testSetAdminFalseIsAccepted(): void
    {
        $this->assertFalse((User::newServiceUser())->setAdmin(false)->isAdmin());
    }

    public function testEnablingTwoFactorAuthenticationThrows(): void
    {
        $this->expectException(InvalidServiceUserException::class);

        (User::newServiceUser())->setTwoFactorAuthentication('enabled', true);
    }

    public function testSaveRejectsTruthyTwoFactorFlagsWrittenAroundTheSetter(): void
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . 'two-factor-raw');
        // stored data that bypassed the setter, as a loaded row could carry it
        (new ReflectionProperty(User::class, 'twoFactorAuthentication'))->setValue($serviceUser, ['enabled' => 1]);

        $this->expectException(InvalidServiceUserException::class);

        $serviceUser->save();
    }

    public function testTwoFactorAuthenticationIsDisabled(): void
    {
        $serviceUser = User::newServiceUser();

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

    public function testASavedPersonCannotBecomeAServiceUser(): void
    {
        $human = $this->createHuman('disguised');
        $human->setType('serviceuser');

        try {
            $this->expectException(InvalidServiceUserException::class);
            $human->save();
        } finally {
            $this->clearUserCache($human->getId());
            $this->assertFalse(User::getById($human->getId())?->isServiceUser());
        }
    }

    public function testSessionRefreshRefusesAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('session');

        $this->expectException(UserNotFoundException::class);
        (new UserProvider())->refreshUser(new SecurityUser($serviceUser));
    }

    public function testServiceUserNameTakenByHumanIsRejected(): void
    {
        $human = $this->createHuman('shared-1');

        $serviceUser = User::newServiceUser();
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
        $this->assertSame('integration-owner@example.com', User::getById($serviceUser->getId())->getEmail());
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
            $this->assertIsServiceUser($item);
        }
    }

    public function testDeletingRoleRemovesItFromServiceUsers(): void
    {
        $role = new Role();
        $role->setName(self::NAME_PREFIX . 'role-cleanup');
        $role->save();
        $this->createdEntities[] = $role;

        $serviceUser = User::newServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . 'role-holder');
        $serviceUser->setRoles([$role->getId()]);
        $serviceUser->save();
        $this->createdEntities[] = $serviceUser;

        $role->delete();

        $this->clearUserCache($serviceUser->getId());
        $this->assertSame([], User::getById($serviceUser->getId())->getRoles());
    }

    public function testWorkspacesAreLoadedForAServiceUserThroughEveryLookup(): void
    {
        $serviceUser = $this->createServiceUserWithWorkspaces('workspaces-load');
        $id = $serviceUser->getId();

        $this->clearUserCache($id);
        $this->assertWorkspacesPresent(User::getById($id));

        $this->clearUserCache($id);
        $this->assertWorkspacesPresent(User::getById($id));

        $this->clearUserCache($id);
        $this->assertWorkspacesPresent(User::getServiceUserByName(self::NAME_PREFIX . 'workspaces-load'));

        $this->clearUserCache($id);
        $listing = new ServiceUserListing();
        $listing->setCondition('id = ?', [$id]);
        $loaded = $listing->load();
        $this->assertCount(1, $loaded);
        $this->assertWorkspacesPresent($loaded[0]);
    }

    public function testResavingALoadedServiceUserKeepsItsWorkspaceRows(): void
    {
        $serviceUser = $this->createServiceUserWithWorkspaces('workspaces-resave');
        $id = $serviceUser->getId();

        $this->clearUserCache($id);
        User::getById($id)->save();

        $this->assertWorkspaceRowsPersisted($id);
    }

    public function testDeletingARoleKeepsTheWorkspaceRowsOfItsServiceUsers(): void
    {
        $role = new Role();
        $role->setName(self::NAME_PREFIX . 'role-workspaces');
        $role->save();
        $this->createdEntities[] = $role;

        $serviceUser = $this->createServiceUserWithWorkspaces('workspaces-role');
        $serviceUser->setRoles([$role->getId()]);
        $serviceUser->save();
        $id = $serviceUser->getId();

        $this->clearUserCache($id);
        $role->delete();

        $this->assertWorkspaceRowsPersisted($id);
        $this->clearUserCache($id);
        $this->assertSame([], User::getById($id)->getRoles());
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
        $this->assertIsServiceUser($version->getUser());
    }

    public function testEditlockHolderMayBeAServiceUser(): void
    {
        $serviceUser = $this->createServiceUser('lock-holder');

        $lock = new Editlock();
        $lock->setUserId($serviceUser->getId());

        $this->assertIsServiceUser($lock->getUser());
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
            $this->assertIsServiceUser($loaded->getSender());
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
        $translation->addTranslation($allowed, 'allowed');
        $translation->addTranslation($forbidden, 'forbidden');

        // Translation\Dao::save() takes the modifying user from the current user, not from setUserModification()
        $tokenStorage = $this->tokenStorage();
        $previousToken = $tokenStorage->getToken();
        $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($serviceUser), 'pimcore_admin'));

        try {
            $translation->save();
        } finally {
            $tokenStorage->setToken($previousToken);
        }

        try {
            $this->assertSame($serviceUser->getId(), $translation->getUserModification());
            $this->clearTranslationCache($key);
            $stored = Translation::getByKey($key, Translation::DOMAIN_DEFAULT);

            $this->assertNotNull($stored);
            $this->assertSame('allowed', $stored->getTranslations()[$allowed] ?? null);
            $this->assertArrayNotHasKey($forbidden, $stored->getTranslations());
        } finally {
            $translation->delete();
        }
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container; Admin::getCurrentUser() reads the
        // token storage held by the public TokenStorageUserResolver service, so reach it through the resolver
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }

    private function clearTranslationCache(string $key): void
    {
        $cacheKey = 'translation_' . $key . '_' . Translation::DOMAIN_DEFAULT;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }

    private function createServiceUser(string $suffix): User
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . $suffix);
        $serviceUser->save();
        $this->createdEntities[] = $serviceUser;

        return $serviceUser;
    }

    private function createServiceUserWithWorkspaces(string $suffix): User
    {
        $serviceUser = $this->createServiceUser($suffix);

        $asset = new AssetWorkspace();
        $asset->setCid(1)->setCpath('/')->setList(true)->setView(true);
        $document = new DocumentWorkspace();
        $document->setCid(1)->setCpath('/')->setList(true)->setView(true);
        $object = new DataObjectWorkspace();
        $object->setCid(1)->setCpath('/')->setList(true)->setView(true);

        $serviceUser->setWorkspacesAsset([$asset]);
        $serviceUser->setWorkspacesDocument([$document]);
        $serviceUser->setWorkspacesObject([$object]);
        $serviceUser->save();

        $this->assertWorkspaceRowsPersisted($serviceUser->getId());

        return $serviceUser;
    }

    private function assertIsServiceUser(mixed $user): void
    {
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isServiceUser());
    }

    private function assertWorkspacesPresent(?User $user): void
    {
        $this->assertIsServiceUser($user);
        $this->assertCount(1, $user->getWorkspacesAsset());
        $this->assertCount(1, $user->getWorkspacesDocument());
        $this->assertCount(1, $user->getWorkspacesObject());
        $this->assertTrue($user->getWorkspacesObject()[0]->getView());
    }

    private function assertWorkspaceRowsPersisted(?int $userId): void
    {
        $db = Db::get();
        foreach (['asset', 'document', 'object'] as $type) {
            $this->assertSame(
                1,
                (int) $db->fetchOne('SELECT COUNT(*) FROM users_workspaces_' . $type . ' WHERE userId = ?', [$userId]),
                'workspace rows of type ' . $type
            );
        }
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
