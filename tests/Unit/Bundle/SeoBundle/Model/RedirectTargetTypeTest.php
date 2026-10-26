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

namespace Pimcore\Tests\Unit\Bundle\SeoBundle\Model;

use InvalidArgumentException;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Element\ElementType;
use Pimcore\Tests\Support\Test\TestCase;

class RedirectTargetTypeTest extends TestCase
{
    public function testTargetTypeDefaultsToNull(): void
    {
        $this->assertNull((new Redirect())->getTargetType());
    }

    public function testTargetTypeAcceptsEnum(): void
    {
        $redirect = new Redirect();
        $redirect->setTargetType(ElementType::Asset);

        $this->assertSame(ElementType::Asset, $redirect->getTargetType());
    }

    /**
     * The DAO and the CSV import hydrate the model from raw scalar values.
     */
    public function testTargetTypeAcceptsStringValue(): void
    {
        $redirect = new Redirect();
        $redirect->setValues(['targetType' => 'object']);

        $this->assertSame(ElementType::Object, $redirect->getTargetType());
    }

    public function testEmptyTargetTypeIsNormalizedToNull(): void
    {
        $redirect = new Redirect();
        $redirect->setTargetType(ElementType::Document);
        $redirect->setTargetType('');

        $this->assertNull($redirect->getTargetType());
    }

    public function testInvalidTargetTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Redirect())->setTargetType('variant');
    }
}
