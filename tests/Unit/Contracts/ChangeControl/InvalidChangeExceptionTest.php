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

use Pimcore\Contracts\ChangeControl\Exception\ChangeControlExceptionInterface;
use Pimcore\Contracts\ChangeControl\Exception\InvalidChangeException;
use Pimcore\Contracts\ChangeControl\ObjectRef;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;

class InvalidChangeExceptionTest extends TestCase
{
    public function testNamesTheObjectItIsForAndChainsItsCause(): void
    {
        $object = ObjectRef::token('sku-1');
        $cause = new RuntimeException('No such class.');
        $exception = new InvalidChangeException('The class does not exist.', $object, $cause);

        self::assertInstanceOf(ChangeControlExceptionInterface::class, $exception);
        self::assertSame('The class does not exist.', $exception->getMessage());
        self::assertSame($object, $exception->object);
        self::assertSame($cause, $exception->getPrevious());
    }

    public function testTheObjectIsOptional(): void
    {
        self::assertNull((new InvalidChangeException('The class does not exist.'))->object);
    }
}
