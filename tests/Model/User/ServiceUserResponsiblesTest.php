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

use InvalidArgumentException;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model\User;
use Pimcore\Model\User\AbstractUser;
use Pimcore\Model\User\Exception\InvalidServiceUserException;
use Pimcore\Model\User\Role;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Authentication;

class ServiceUserResponsiblesTest extends ModelTestCase
{
    private const NAME_PREFIX = 'svc-resp-';

    /**
     * @var AbstractUser[]
     */
    private array $created = [];

    /**
     * @var array<array{string, callable}>
     */
    private array $registeredListeners = [];

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        foreach (array_reverse($this->created) as $entity) {
            if ($entity->getId() && AbstractUser::getById($entity->getId())) {
                $entity->delete();
            }
        }
        $this->created = [];

        parent::tearDown();
    }

    public function testResponsiblesArePersistedAndReloaded(): void
    {
        $human = $this->createHuman('h1');
        $role = $this->createRole('r1');
        $serviceUser = $this->createServiceUser('s1');

        $serviceUser->setResponsibleUsers([$human->getId()])->setResponsibleRoles([$role->getId()])->save();

        $this->clearUserCache($serviceUser->getId());
        $loaded = User::getById($serviceUser->getId());

        $this->assertSame([$human->getId()], $loaded->getResponsibleUsers());
        $this->assertSame([$role->getId()], $loaded->getResponsibleRoles());

        // saving again replaces, it does not append
        $loaded->setResponsibleUsers([])->save();
        $this->clearUserCache($serviceUser->getId());
        $this->assertSame([], User::getById($serviceUser->getId())->getResponsibleUsers());
        $this->assertSame([$role->getId()], User::getById($serviceUser->getId())->getResponsibleRoles());
    }

    public function testSettersNormalizeAndValidateIds(): void
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setResponsibleUsers(['5', 5, 7]);
        $this->assertSame([5, 7], $serviceUser->getResponsibleUsers());

        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type (deliberately invalid input)
        $serviceUser->setResponsibleRoles(['abc']);
    }

    public function testHumanUserWithResponsiblesIsRefused(): void
    {
        $human = new User();
        $human->setName(self::NAME_PREFIX . 'refused');
        $human->setPassword(Authentication::getPasswordHash(self::NAME_PREFIX . 'refused', 'a-long-test-password'));
        $human->setResponsibleRoles([1]);

        $this->expectException(InvalidServiceUserException::class);
        $human->save();
    }

    public function testDeletingTheServiceUserRemovesItsRows(): void
    {
        $human = $this->createHuman('h2');
        $serviceUser = $this->createServiceUser('s2');
        $serviceUser->setResponsibleUsers([$human->getId()])->save();
        $id = $serviceUser->getId();

        $serviceUser->delete();

        $this->assertSame(0, $this->countRows('serviceUserId', $id));
        $this->assertNotNull(User::getById($human->getId()));
    }

    public function testDeletingAResponsibleUserRemovesTheRows(): void
    {
        $human = $this->createHuman('h3');
        $other = $this->createHuman('h3b');
        $serviceUser = $this->createServiceUser('s3');
        $serviceUser->setResponsibleUsers([$human->getId(), $other->getId()])->save();

        $human->delete();

        $this->assertSame(0, $this->countRows('principalId', $human->getId(), 'user'));
        $this->clearUserCache($serviceUser->getId());
        $this->assertSame([$other->getId()], User::getById($serviceUser->getId())->getResponsibleUsers());
    }

    public function testDeletingAResponsibleRoleRemovesTheRows(): void
    {
        $role = $this->createRole('r4');
        $serviceUser = $this->createServiceUser('s4');
        $serviceUser->setRoles([$role->getId()]);
        $serviceUser->setResponsibleRoles([$role->getId()])->save();

        $role->delete();

        $this->assertSame(0, $this->countRows('principalId', $role->getId(), 'role'));
        $this->clearUserCache($serviceUser->getId());
        $this->assertSame([], User::getById($serviceUser->getId())->getResponsibleRoles());
    }

    public function testReverseLookupFindsDirectAndRoleResponsibility(): void
    {
        $role = $this->createRole('r5');
        $member = $this->createHuman('h5');
        $member->setRoles([$role->getId()])->save();
        $direct = $this->createHuman('h5b');
        $stranger = $this->createHuman('h5c');

        $viaRole = $this->createServiceUser('s5a');
        $viaRole->setResponsibleRoles([$role->getId()])->save();
        $viaUser = $this->createServiceUser('s5b');
        $viaUser->setResponsibleUsers([$direct->getId()])->save();
        $unrelated = $this->createServiceUser('s5c');
        $unrelated->setResponsibleUsers([$stranger->getId()])->save();

        $this->assertSame([$viaRole->getId()], User::getResponsibleForServiceUserIds($member));
        $this->assertSame([$viaUser->getId()], User::getResponsibleForServiceUserIds($direct));
        $this->assertSame([$unrelated->getId()], User::getResponsibleForServiceUserIds($stranger));
        $this->assertSame([], User::getResponsibleForServiceUserIds($viaRole));
    }

    public function testRoleIdIsNotConfusedWithUserId(): void
    {
        // a user id never matches a row whose principal is a role, and the other way around
        $role = $this->createRole('r6');
        $serviceUser = $this->createServiceUser('s6');
        $serviceUser->setResponsibleRoles([$role->getId()])->save();
        $human = $this->createHuman('h6');

        $this->assertSame([], User::getResponsibleForServiceUserIds($human));
    }

    public function testResolveResponsibleUsersExpandsRolesAndSkipsInactiveAndServiceUsers(): void
    {
        $role = $this->createRole('r7');
        $direct = $this->createHuman('h7a');
        $roleMember = $this->createHuman('h7b');
        $roleMember->setRoles([$role->getId()])->save();
        $inactive = $this->createHuman('h7c');
        $inactive->setRoles([$role->getId()])->setActive(false)->save();
        $serviceMember = $this->createServiceUser('s7member');
        $serviceMember->setRoles([$role->getId()])->save();

        $serviceUser = $this->createServiceUser('s7');
        $serviceUser->setResponsibleUsers([$direct->getId(), $inactive->getId()])
            ->setResponsibleRoles([$role->getId()])
            ->save();

        $ids = array_map(static fn (User $u): ?int => $u->getId(), $serviceUser->resolveResponsibleUsers());
        sort($ids);
        $expected = [$direct->getId(), $roleMember->getId()];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function testServiceUserIdAsResponsibleUserIsRefused(): void
    {
        $other = $this->createServiceUser('s8other');
        $serviceUser = $this->createServiceUser('s8');
        $serviceUser->setResponsibleUsers([$other->getId()]);

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->save();
    }

    public function testRoleIdAsResponsibleUserIsRefused(): void
    {
        $role = $this->createRole('r9');
        $serviceUser = $this->createServiceUser('s9');
        $serviceUser->setResponsibleUsers([$role->getId()]);

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->save();
    }

    public function testUserIdAsResponsibleRoleIsRefused(): void
    {
        $human = $this->createHuman('h10');
        $serviceUser = $this->createServiceUser('s10');
        $serviceUser->setResponsibleRoles([$human->getId()]);

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->save();
    }

    public function testDeletedResponsibleIdIsRefusedAndNoRowIsWritten(): void
    {
        $human = $this->createHuman('h11');
        $serviceUser = $this->createServiceUser('s11');
        $serviceUser->setResponsibleUsers([$human->getId()])->save();

        $human->delete();

        $this->expectException(InvalidServiceUserException::class);
        try {
            $serviceUser->save();
        } finally {
            $this->assertSame(0, $this->countRows('serviceUserId', $serviceUser->getId()));
        }
    }

    public function testOrphanRowIsIgnoredOnLoadAndDroppedOnSave(): void
    {
        // a principal deleted while the service user was saved can leave a row behind; it must not block the user
        $human = $this->createHuman('h12');
        $role = $this->createRole('r12');
        $serviceUser = $this->createServiceUser('s12');
        $serviceUser->setResponsibleUsers([$human->getId()])->setResponsibleRoles([$role->getId()])->save();

        $missingId = $this->unusedUserId();
        $this->insertRow($serviceUser->getId(), 'user', $missingId);
        $this->insertRow($serviceUser->getId(), 'role', $missingId);
        // an existing id stored with the wrong type is not a valid principal either
        $this->insertRow($serviceUser->getId(), 'role', $human->getId());

        $this->clearUserCache($serviceUser->getId());
        $loaded = User::getById($serviceUser->getId());
        $this->assertSame([$human->getId()], $loaded->getResponsibleUsers());
        $this->assertSame([$role->getId()], $loaded->getResponsibleRoles());

        $loaded->save();

        $this->assertSame(2, $this->countRows('serviceUserId', $serviceUser->getId()));
        $this->assertSame(0, $this->countRows('principalId', $missingId));
    }

    public function testReverseLookupIgnoresOrphanRows(): void
    {
        $serviceUser = $this->createServiceUser('s13');
        $missingRoleId = $this->unusedUserId();
        $this->insertRow($serviceUser->getId(), 'role', $missingRoleId);

        // a person whose stored roles still list the deleted role id
        $human = $this->createHuman('h13');
        $human->setRoles([$missingRoleId]);

        $this->assertSame([], User::getResponsibleForServiceUserIds($human));
    }

    public function testResponsibleSetByAPreUpdateListenerIsCheckedAndNoRowIsWritten(): void
    {
        $human = $this->createHuman('h14');
        $serviceUser = $this->createServiceUser('s14');
        $serviceUser->setResponsibleUsers([$human->getId()])->save();
        $otherServiceUser = $this->createServiceUser('s14other');
        $serviceUserId = $serviceUser->getId();

        // the listener runs after save() started, so a check made before the event never sees this id
        $this->addListener(
            UserRoleEvents::PRE_UPDATE,
            static function (UserRoleEvent $event) use ($serviceUserId, $otherServiceUser): void {
                $user = $event->getUserRole();
                if ($user instanceof User && $user->getId() === $serviceUserId) {
                    $user->setResponsibleUsers([$otherServiceUser->getId()]);
                }
            }
        );

        try {
            $serviceUser->save();
            $this->fail('A service user id set as responsible user by a listener must be refused.');
        } catch (InvalidServiceUserException) {
            // expected
        }

        // the transaction is rolled back: the stored responsible user is unchanged, no row names the service user
        $this->assertSame(1, $this->countRows('serviceUserId', $serviceUserId));
        $this->assertSame(0, $this->countRows('principalId', $otherServiceUser->getId(), 'user'));
    }

    public function testResponsibleSetByAPreAddListenerIsChecked(): void
    {
        $missingId = $this->unusedUserId();
        $this->addListener(UserRoleEvents::PRE_ADD, static function (UserRoleEvent $event) use ($missingId): void {
            $user = $event->getUserRole();
            if ($user instanceof User && $user->isServiceUser()) {
                $user->setResponsibleRoles([$missingId]);
            }
        });

        $serviceUser = User::newServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . 's15');

        try {
            $serviceUser->save();
            $this->fail('A missing role id set as responsible role by a listener must be refused.');
        } catch (InvalidServiceUserException) {
            // expected
        }

        $this->assertNull(User::getServiceUserByName(self::NAME_PREFIX . 's15'));
        $this->assertSame(0, $this->countRows('principalId', $missingId, 'role'));
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
    }

    private function unusedUserId(): int
    {
        return (int) Db::get()->fetchOne('SELECT COALESCE(MAX(id), 0) + 1000 FROM users');
    }

    private function insertRow(int $serviceUserId, string $principalType, int $principalId): void
    {
        Db::get()->insert('users_responsibles', [
            'serviceUserId' => $serviceUserId,
            'principalType' => $principalType,
            'principalId' => $principalId,
        ]);
    }

    private function countRows(string $column, int $value, ?string $principalType = null): int
    {
        $sql = 'SELECT COUNT(*) FROM users_responsibles WHERE `' . $column . '` = ?';
        $params = [$value];
        if ($principalType !== null) {
            $sql .= ' AND principalType = ?';
            $params[] = $principalType;
        }

        return (int) Db::get()->fetchOne($sql, $params);
    }

    private function createServiceUser(string $suffix): User
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setName(self::NAME_PREFIX . $suffix);
        $serviceUser->save();
        $this->created[] = $serviceUser;

        return $serviceUser;
    }

    private function createHuman(string $suffix): User
    {
        $human = new User();
        $human->setName(self::NAME_PREFIX . $suffix);
        $human->setPassword(Authentication::getPasswordHash(self::NAME_PREFIX . $suffix, 'a-long-test-password'));
        $human->save();
        $this->created[] = $human;

        return $human;
    }

    private function createRole(string $suffix): Role
    {
        $role = new Role();
        $role->setName(self::NAME_PREFIX . $suffix);
        $role->save();
        $this->created[] = $role;

        return $role;
    }

    private function clearUserCache(?int $id): void
    {
        $cacheKey = 'user_' . $id;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }
}
