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

namespace Pimcore\Tests\Unit\Model\Workflow\SupportStrategy;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\SupportStrategy\ExpressionSupportStrategy;
use stdClass;
use Symfony\Component\Workflow\WorkflowInterface;

class ExpressionSupportStrategyTest extends TestCase
{
    /**
     * Regression: supports() unconditionally evaluated the configured expression, even when
     * it was blank. Symfony's ExpressionLanguage throws a syntax error when parsing an empty
     * string, which propagated out of the workflow registry lookup and broke every action on
     * the workflow instead of the support strategy simply falling back to matching on class
     * alone.
     */
    public function testSupportsDoesNotEvaluateBlankExpression(): void
    {
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->never())->method('evaluateExpression');

        $strategy = new ExpressionSupportStrategy($expressionService, stdClass::class, '');

        $result = $strategy->supports($this->createStub(WorkflowInterface::class), new stdClass());

        $this->assertTrue($result);
    }

    public function testSupportsReturnsFalseForUnsupportedClassEvenWithBlankExpression(): void
    {
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->never())->method('evaluateExpression');

        $strategy = new ExpressionSupportStrategy($expressionService, self::class, '');

        $result = $strategy->supports($this->createStub(WorkflowInterface::class), new stdClass());

        $this->assertFalse($result);
    }

    public function testSupportsStillEvaluatesNonBlankExpression(): void
    {
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->once())
            ->method('evaluateExpression')
            ->willReturn(false);

        $strategy = new ExpressionSupportStrategy($expressionService, stdClass::class, 'subject.foo == "bar"');

        $result = $strategy->supports($this->createStub(WorkflowInterface::class), new stdClass());

        $this->assertFalse($result);
    }
}
