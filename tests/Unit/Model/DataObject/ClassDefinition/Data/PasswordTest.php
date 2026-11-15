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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Password::getDataForResource() must hash plain text exactly once: a value that already is a
 * password hash has to be persisted untouched, otherwise every save would hash the hash and
 * verifyPassword() would stop matching. The check relies on password_get_info() reporting
 * null as algorithm for anything that is not a recognised hash.
 */
class PasswordTest extends TestCase
{
    public function testPlainTextIsHashedAndAnExistingHashIsKept(): void
    {
        $field = new Password();
        $field->setName('password');

        $hash = $field->getDataForResource('secret');

        $this->assertIsString($hash);
        $this->assertNotSame('secret', $hash);
        $this->assertTrue(password_verify('secret', $hash));
        $this->assertNotNull(password_get_info($hash)['algo']);

        $this->assertSame($hash, $field->getDataForResource($hash), 'an already hashed value must not be hashed again');
    }

    public function testEmptyValueIsStoredAsNull(): void
    {
        $field = new Password();
        $field->setName('password');

        $this->assertNull($field->getDataForResource(null));
        $this->assertNull($field->getDataForResource(''));
    }
}
