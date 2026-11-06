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

namespace Pimcore\Tests\Unit\Contracts\ChangeControl;

use InvalidArgumentException;
use Pimcore\Contracts\ChangeControl\ObjectRef;
use Pimcore\Tests\Support\Test\TestCase;

class ObjectRefTest extends TestCase
{
    public function testNamesAnObjectByIdByTokenOrBothOnceCreated(): void
    {
        self::assertSame([42, null], [ObjectRef::of(42)->id, ObjectRef::of(42)->token]);
        self::assertSame([null, 'sku-1'], [ObjectRef::token('sku-1')->id, ObjectRef::token('sku-1')->token]);
        self::assertSame([42, 'sku-1'], [ObjectRef::created('sku-1', 42)->id, ObjectRef::created('sku-1', 42)->token]);
    }

    public function testATokenMayBeEightyCharactersLong(): void
    {
        self::assertSame(str_repeat('a', 80), ObjectRef::token(str_repeat('a', 80))->token);
    }

    /**
     * @dataProvider invalidTokens
     */
    public function testRefusesAToken(string $token): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no token');
        ObjectRef::token($token);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTokens(): array
    {
        return [
            'empty' => [''],
            'a space' => ['a b'],
            'a path' => ['a/b'],
            'a trailing line break' => ["sku\n"],
            'too long' => [str_repeat('a', 81)],
        ];
    }

    public function testRefusesAnIdBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive id');
        ObjectRef::of(0);
    }

    public function testRefusesACreatedObjectWithAnIdBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive id');
        ObjectRef::created('sku-1', 0);
    }

    public function testRefusesACreatedObjectWithAnInvalidToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no token');
        ObjectRef::created('a b', 1);
    }
}
