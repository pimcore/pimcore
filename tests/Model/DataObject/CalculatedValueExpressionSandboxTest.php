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

use Pimcore;
use Pimcore\Helper\SymfonyExpression\CalculatedFieldExpressionLanguage;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\Note;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Calculated value expressions are evaluated on every read of the object, so they must only be
 * able to read data of the object graph - never reach the database connection, run SQL built
 * from expression input, or persist anything (GHSA-93jv-p24f-gcpp).
 *
 * @group model.dataobject.calculatedvalue
 */
class CalculatedValueExpressionSandboxTest extends ModelTestCase
{
    private CalculatedFieldExpressionLanguage $expressionLanguage;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->expressionLanguage = Pimcore::getContainer()->get('pimcore.calculated_value.expression_language');
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testAllowsFieldAndSystemGetters(): void
    {
        $object = $this->createObject();

        $this->assertSame('sandbox', $this->evaluate('object.getInput()', $object));
        $this->assertSame($object->getId(), $this->evaluate('object.getId()', $object));
        $this->assertSame($object->getKey(), $this->evaluate('object.getKey()', $object));
        $this->assertSame(1, $this->evaluate('object.getParent().getId()', $object));
        $this->assertSame('sandbox', $this->evaluate('object.getLinput("en") ?? object.getInput()', $object));
    }

    public function testAllowsFieldGettersOverriddenByAnApplicationModel(): void
    {
        // a model override (e.g. supplying inherited data) declares the getter on the subclass
        $object = new class() extends Unittest {
            public function getInput(): ?string
            {
                return 'overridden:' . parent::getInput();
            }
        };
        $object->setInput('sandbox');

        $this->assertSame('overridden:sandbox', $this->evaluate('object.getInput()', $object));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenExpressionProvider(): iterable
    {
        yield 'dao via getObjectVar' => ['object.getObjectVar("dao").db'];
        yield 'dao via getValueForFieldName' => ['object.getValueForFieldName("dao").db'];
        yield 'static getList with SQL condition' => ['object.getList({"condition": "1=1"}).getTotalCount()'];
        yield 'static getList via getValueFromParent' => ['object.getValueFromParent("List", {"condition": "1=1"})'];
        yield 'static getById' => ['object.getById(1)'];
        yield 'SQL via getRelationData' => ['object.getRelationData("objects", true, "1")'];
        yield 'SQL via hasSiblings' => ['object.hasSiblings(["object"])'];
        yield 'class definition' => ['object.getClass().getName()'];
        yield 'children listing' => ['object.getChildren().getTotalCount()'];
    }

    /**
     * @dataProvider forbiddenExpressionProvider
     */
    public function testRejectsForbiddenExpressions(string $expression): void
    {
        $object = $this->createObject();

        $this->expectException(SyntaxError::class);
        $this->evaluate($expression, $object);
    }

    public function testConsentEditmodeConversionCannotPersistNotes(): void
    {
        $object = $this->createObject();
        $notesBefore = $this->countNotes($object);

        try {
            $this->evaluate(
                'object.getClass().getFieldDefinition("consent").getDataFromEditmode(true, object)',
                $object
            );
            $this->fail('Expected the editmode conversion to be rejected');
        } catch (SyntaxError) {
        }

        $this->assertSame($notesBefore, $this->countNotes($object));
    }

    private function createObject(): Unittest
    {
        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject('', false);
        $object->setInput('sandbox');
        $object->save();

        return $object;
    }

    private function evaluate(string $expression, Unittest $object): mixed
    {
        return $this->expressionLanguage->evaluate($expression, ['object' => $object, 'data' => null]);
    }

    private function countNotes(Unittest $object): int
    {
        $list = new Note\Listing();
        $list->setCondition('cid = ? AND ctype = ?', [$object->getId(), 'object']);

        return $list->getTotalCount();
    }
}
