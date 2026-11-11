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
use Pimcore\Contracts\ChangeControl\NewDataObject;
use Pimcore\Tests\Support\Test\TestCase;

class NewDataObjectTest extends TestCase
{
    public function testAParentIsAnIdOrTheTokenOfAnotherNewObject(): void
    {
        self::assertSame(12, (new NewDataObject('chair', 'Product', 12, 'chair'))->parent);
        self::assertSame('folder', (new NewDataObject('chair', 'Product', 'folder', 'chair'))->parent);
    }

    /**
     * @dataProvider invalidObjects
     */
    public function testRefuses(string $token, string $class, int|string $parent, string $key, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new NewDataObject($token, $class, $parent, $key);
    }

    /**
     * @return array<string, array{string, string, int|string, string, string}>
     */
    public static function invalidObjects(): array
    {
        return [
            'an invalid token' => ['a b', 'Product', 1, 'key', 'no token'],
            'an empty class' => ['chair', ' ', 1, 'key', 'class name'],
            'a parent id below 1' => ['chair', 'Product', 0, 'key', 'positive id'],
            'an invalid parent token' => ['chair', 'Product', 'a/b', 'key', 'no token'],
            'itself as its parent' => ['chair', 'Product', 'chair', 'key', 'its own parent'],
            'an empty key' => ['chair', 'Product', 1, ' ', 'needs a key'],
        ];
    }
}
