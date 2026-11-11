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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Helper\SymfonyExpression\ReadOnlyExpressionLanguage;
use Pimcore\Helper\SymfonyExpression\ReadOnlyWorkflowExpressionLanguage;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Workflow guards, the workflow designer's expressions and bundles (e.g. the collab bundle) use
 * the `workflow.security.expression_language` service; it has to evaluate against read-only
 * stand-ins (GHSA-93jv-p24f-gcpp).
 *
 * @group model.dataobject.calculatedvalue
 */
class ReadOnlyExpressionLanguageWiringTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testWorkflowExpressionLanguageIsReadOnly(): void
    {
        $expressionLanguage = $this->grabService('workflow.security.expression_language');
        $this->assertInstanceOf(ReadOnlyWorkflowExpressionLanguage::class, $expressionLanguage);

        $subject = TestHelper::createEmptyObject();
        $this->assertSame($subject->getKey(), $expressionLanguage->evaluate('subject.getKey()', ['subject' => $subject]));

        $this->expectException(SyntaxError::class);
        $expressionLanguage->evaluate('subject.getDao()', ['subject' => $subject]);
    }

    public function testReadOnlyExpressionLanguageServiceIsAvailable(): void
    {
        $expressionLanguage = $this->grabService(ReadOnlyExpressionLanguage::class);
        $subject = TestHelper::createEmptyObject();

        $this->assertSame($subject->getId(), $expressionLanguage->evaluate('object.getId()', ['object' => $subject]));

        $this->expectException(SyntaxError::class);
        $expressionLanguage->evaluate('object.delete()', ['object' => $subject]);
    }

    private function grabService(string $serviceId): object
    {
        return $this->getModule('\\' . PimcoreHelper::class)->grabService($serviceId);
    }
}
