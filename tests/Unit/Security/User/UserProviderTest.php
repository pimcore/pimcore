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

namespace Pimcore\Tests\Unit\Security\User;

use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\User;
use Pimcore\Security\User\UserProvider;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;

class UserProviderTest extends TestCase
{
    private const USERNAME = 'user-provider-test-user';

    protected UserProvider $userProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProvider = new UserProvider();
        $this->deleteTestUser();
    }

    public function _after(): void
    {
        $this->deleteTestUser();
    }

    public function testRefreshUserReturnsUserFromDatabase(): void
    {
        $pimcoreUser = $this->createTestUser();

        $refreshedUser = $this->userProvider->refreshUser(new User($pimcoreUser));

        $this->assertInstanceOf(User::class, $refreshedUser);
        $this->assertSame($pimcoreUser->getId(), $refreshedUser->getId());
        $this->assertSame(self::USERNAME, $refreshedUser->getUserIdentifier());
    }

    public function testRefreshUserThrowsUserNotFoundExceptionIfUserWasDeleted(): void
    {
        $pimcoreUser = $this->createTestUser();
        $user = new User($pimcoreUser);

        // the session outlives the user it was created for
        $pimcoreUser->delete();

        $this->expectException(UserNotFoundException::class);

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserThrowsUserNotFoundExceptionIfPasswordWasChanged(): void
    {
        $pimcoreUser = $this->createTestUser();

        // a session which was created before the password of the user was reset
        $userFromSession = new PimcoreUser();
        $userFromSession->setId($pimcoreUser->getId());
        $userFromSession->setName(self::USERNAME);
        $userFromSession->setLastPasswordReset(time());

        $this->expectException(UserNotFoundException::class);

        $this->userProvider->refreshUser(new User($userFromSession));
    }

    public function testRefreshUserThrowsUnsupportedUserExceptionForNonPimcoreUsers(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $this->userProvider->refreshUser(new InMemoryUser(self::USERNAME, null));
    }

    protected function createTestUser(): PimcoreUser
    {
        $user = PimcoreUser::create([
            'parentId' => 0,
            'name' => self::USERNAME,
            'active' => true,
        ]);

        $this->assertNotNull(PimcoreUser::getById($user->getId()));

        return $user;
    }

    protected function deleteTestUser(): void
    {
        $user = PimcoreUser::getByName(self::USERNAME);

        if ($user instanceof PimcoreUser) {
            $user->delete();
        }
    }
}
