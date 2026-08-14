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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;

final class GetLinkGeneratorTest extends TestCase
{
    public function testNullLinkGeneratorReferenceReturnsNull(): void
    {
        $classDefinition = new ClassDefinition();

        $this->assertNull($classDefinition->getLinkGenerator());
    }

    public function testLinkGeneratorReferenceIsResolvedToInstance(): void
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setLinkGeneratorReference(TestLinkGenerator::class);

        $this->assertInstanceOf(TestLinkGenerator::class, $classDefinition->getLinkGenerator());
    }
}

final class TestLinkGenerator implements LinkGeneratorInterface
{
    public function generate(object $object, array $params = []): string
    {
        return '/test-link';
    }
}
