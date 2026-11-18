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

use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Containers build the legacy message from the original exceptions, so a custom ValidationException that
 * overrides getAggregatedMessage() keeps its text; the structured violation still names the field.
 */
class ContainerLegacyMessageTest extends TestCase
{
    public function testBlockKeepsTheAggregatedMessageOfACustomException(): void
    {
        $child = new class() extends Input {
            public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
            {
                throw new FormattedValidationException('raw');
            }
        };
        $child->setName('label');

        $block = new Block();
        $block->setName('variants');
        $block->setChildren([$child]);

        try {
            $block->checkValidity([['label' => new BlockElement('label', 'input', 'x')]]);
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame('custom: raw', $exception->getMessage());
            $this->assertCount(1, $exception->getViolations());
            $this->assertSame('label', $exception->getViolations()[0]->getFieldName());
        }
    }
}

/**
 * @internal
 */
class FormattedValidationException extends ValidationException
{
    public function getAggregatedMessage(): string
    {
        return 'custom: ' . $this->getMessage();
    }
}
