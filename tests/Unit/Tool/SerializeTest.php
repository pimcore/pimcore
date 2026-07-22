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

namespace Pimcore\Tests\Unit\Tool;

use __PHP_Incomplete_Class;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Serialize;

/**
 * Regression tests for the Serialize::unserialize() wrapper: object deserialization must be
 * disabled by default, so a caller that omits the allowedClasses argument cannot be used to
 * instantiate arbitrary classes from serialized bytes.
 */
class SerializeTest extends TestCase
{
    public function testDefaultDoesNotInstantiateObjects(): void
    {
        $payload = serialize(new SerializeDeserializeCanary());
        SerializeDeserializeCanary::$fired = false;

        $result = Serialize::unserialize($payload);

        $this->assertFalse(
            SerializeDeserializeCanary::$fired,
            'The default must not run a deserialized object\'s magic methods.'
        );
        $this->assertNotInstanceOf(SerializeDeserializeCanary::class, $result);
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $result);
    }

    public function testExplicitFalseDoesNotInstantiateObjects(): void
    {
        $payload = serialize(new SerializeDeserializeCanary());
        SerializeDeserializeCanary::$fired = false;

        $result = Serialize::unserialize($payload, false);

        $this->assertFalse(SerializeDeserializeCanary::$fired);
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $result);
    }

    public function testExplicitAllowlistInstantiatesTheAllowedClass(): void
    {
        $payload = serialize(new SerializeDeserializeCanary());

        $result = Serialize::unserialize($payload, [SerializeDeserializeCanary::class]);

        $this->assertInstanceOf(SerializeDeserializeCanary::class, $result);
    }

    public function testExplicitTrueRemainsPermissiveForOptInCallers(): void
    {
        $payload = serialize(new SerializeDeserializeCanary());

        $result = Serialize::unserialize($payload, true);

        $this->assertInstanceOf(SerializeDeserializeCanary::class, $result);
    }

    public function testScalarAndArrayDataRoundTripUnderTheSafeDefault(): void
    {
        $data = ['a' => 1, 'b' => ['c' => 'text'], 'd' => null];

        $this->assertSame($data, Serialize::unserialize(serialize($data)));
        $this->assertSame('plain', Serialize::unserialize(serialize('plain')));
        $this->assertNull(Serialize::unserialize(null));
        $this->assertSame('', Serialize::unserialize(''));
    }
}

/**
 * Canary whose magic methods flip a static flag, so a test can detect whether it was
 * instantiated (or had its magic methods run) during deserialization.
 */
class SerializeDeserializeCanary
{
    public static bool $fired = false;

    public function __wakeup(): void
    {
        self::$fired = true;
    }

    public function __destruct()
    {
        self::$fired = true;
    }
}
