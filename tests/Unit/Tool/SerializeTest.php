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
use ReflectionProperty;

/**
 * Regression tests for the Serialize::unserialize() wrapper. Callers must be able to restrict
 * object deserialization via allowedClasses; omitting the argument stays permissive for backward
 * compatibility (the default flips to false in Pimcore 2027.1) but must emit a deprecation.
 */
class SerializeTest extends TestCase
{
    /**
     * BC guarantee: omitting the argument still reconstructs objects under the current default,
     * so existing callers are not broken — but the caller is warned to become explicit.
     */
    public function testOmittingAllowedClassesStaysPermissiveButIsDeprecated(): void
    {
        // The deprecation is emitted at most once per process; reset the guard so this test
        // observes it regardless of whether an earlier call already tripped it.
        $guard = new ReflectionProperty(Serialize::class, 'unserializeWithoutAllowedClassesDeprecationTriggered');
        $guard->setValue(null, false);

        $payload = serialize(new SerializeDeserializeCanary());

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $result = Serialize::unserialize($payload);
            // A second omitting call must NOT emit another deprecation — guards against flooding
            // the logs when unserializing in a loop (e.g. a listing of many objects).
            Serialize::unserialize($payload);
            Serialize::unserialize($payload);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(SerializeDeserializeCanary::class, $result);
        $this->assertCount(1, $deprecations, 'The deprecation must be emitted at most once per process.');
        $this->assertStringContainsString('without the $allowedClasses argument is deprecated', $deprecations[0]);
        $this->assertStringContainsString('2027.1', $deprecations[0]);
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

    public function testExplicitFalseAllowsScalarAndArrayRoundTrip(): void
    {
        $data = ['a' => 1, 'b' => ['c' => 'text'], 'd' => null];

        $this->assertSame($data, Serialize::unserialize(serialize($data), false));
        $this->assertSame('plain', Serialize::unserialize(serialize('plain'), false));
        $this->assertNull(Serialize::unserialize(null, false));
        $this->assertSame('', Serialize::unserialize('', false));
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
