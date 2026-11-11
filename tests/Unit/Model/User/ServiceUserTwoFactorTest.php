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

namespace Pimcore\Tests\Unit\Model\User;

use Pimcore\Model\User;
use Pimcore\Model\User\Exception\InvalidServiceUserException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * A service user never gets two-factor authentication. The flags are read by truthiness elsewhere
 * (\Pimcore\Security\User\User::isGoogleAuthenticatorEnabled()), so the guard must treat 1, '1', 'true'
 * and 'on' like true, whether the data arrives as an array or as the JSON string the database stores.
 */
class ServiceUserTwoFactorTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>|string}>
     */
    public static function enablingDataProvider(): array
    {
        return [
            'enabled true' => [['enabled' => true]],
            'enabled int' => [['enabled' => 1]],
            'enabled string' => [['enabled' => '1']],
            'required string true' => [['required' => 'true']],
            'required on' => [['required' => 'on']],
            'secret only' => [['enabled' => false, 'secret' => 'abc']],
            'type only' => [['enabled' => false, 'type' => 'google']],
            'json enabled string' => ['{"enabled":"1","required":false,"secret":"","type":""}'],
        ];
    }

    /**
     * @param array<string, mixed>|string $data
     *
     * @dataProvider enablingDataProvider
     */
    public function testDataThatEnablesTwoFactorAuthenticationIsRefused(array|string $data): void
    {
        $serviceUser = User::newServiceUser();

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->setTwoFactorAuthentication($data);
    }

    public function testDisabledDataIsAcceptedAndReadsAsTheDefaults(): void
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setTwoFactorAuthentication(['enabled' => false, 'required' => '0', 'secret' => '', 'type' => '']);

        $this->assertFalse($serviceUser->getTwoFactorAuthentication('enabled'));
        $this->assertFalse($serviceUser->getTwoFactorAuthentication('required'));
        $this->assertSame('', $serviceUser->getTwoFactorAuthentication('secret'));
    }

    public function testASingleEnablingKeyIsRefused(): void
    {
        $serviceUser = User::newServiceUser();

        $this->expectException(InvalidServiceUserException::class);
        $serviceUser->setTwoFactorAuthentication('enabled', 'on');
    }
}
