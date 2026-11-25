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
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Authentication;

class WelcomeScreenTargetTest extends ModelTestCase
{
    private const NAME = 'welcome-screen-target-user';

    private ?User $user = null;

    protected function tearDown(): void
    {
        if ($this->user?->getId() && User::getById($this->user->getId())) {
            $this->user->delete();
        }
        $this->user = null;

        parent::tearDown();
    }

    public function testTargetIsPersistedAndCanBeCleared(): void
    {
        $user = $this->createUser();

        $user->setWelcomeScreenTarget('studio-dashboards:019c7555')->save();
        $this->assertSame('studio-dashboards:019c7555', $this->reload($user)->getWelcomeScreenTarget());

        $user->setWelcomeScreenTarget(null)->save();
        $this->assertNull($this->reload($user)->getWelcomeScreenTarget());
    }

    private function createUser(): User
    {
        $this->user = new User();
        $this->user->setName(self::NAME);
        $this->user->setPassword(Authentication::getPasswordHash(self::NAME, 'a-long-test-password'));
        $this->user->save();

        return $this->user;
    }

    private function reload(User $user): User
    {
        $cacheKey = 'user_' . $user->getId();
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }

        return User::getById($user->getId());
    }
}
