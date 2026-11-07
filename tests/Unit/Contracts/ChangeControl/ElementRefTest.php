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
use Pimcore\Contracts\ChangeControl\ElementRef;
use Pimcore\Contracts\ChangeControl\ElementType;
use Pimcore\Tests\Support\Test\TestCase;

class ElementRefTest extends TestCase
{
    public function testEachElementTypeHasItsOwnFactory(): void
    {
        self::assertSame([ElementType::DataObject, 1], [ElementRef::object(1)->type, ElementRef::object(1)->id]);
        self::assertSame([ElementType::Asset, 2], [ElementRef::asset(2)->type, ElementRef::asset(2)->id]);
        self::assertSame([ElementType::Document, 3], [ElementRef::document(3)->type, ElementRef::document(3)->id]);
    }

    public function testRefusesAnIdBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive id');
        ElementRef::object(0);
    }
}
