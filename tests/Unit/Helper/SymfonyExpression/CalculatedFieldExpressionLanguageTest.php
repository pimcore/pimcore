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

namespace Pimcore\Tests\Unit\Helper\SymfonyExpression;

use PHPUnit\Framework\TestCase;
use Pimcore\Helper\SymfonyExpression\CalculatedFieldExpressionLanguage;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @internal
 */
final class CalculatedFieldExpressionLanguageTest extends TestCase
{
    public function testAllowsGetPrefixedMethodCalls(): void
    {
        $language = new CalculatedFieldExpressionLanguage();
        $fixture = new ExpressionLanguageTestFixture();

        $this->assertSame(42, $language->evaluate('obj.getId()', ['obj' => $fixture]));
    }

    public function testAllowsIsAndHasPrefixedMethodCalls(): void
    {
        $language = new CalculatedFieldExpressionLanguage();
        $fixture = new ExpressionLanguageTestFixture();

        $this->assertTrue($language->evaluate('obj.isActive()', ['obj' => $fixture]));
        $this->assertTrue($language->evaluate('obj.hasChildren()', ['obj' => $fixture]));
    }

    public function testExistingCalculatorDataGetterExpressionStillWorks(): void
    {
        // Mirrors the expression used by CalculatedValueTest::testCalculatorDataIsPassedToExpression()
        // - the documented `data.getXxx()` contract must keep working.
        $language = new CalculatedFieldExpressionLanguage();
        $data = new CalculatedValue('myField');
        $data->setCalculatorData('expression-arg');

        $this->assertSame('expression-arg', $language->evaluate('data.getCalculatorData()', ['data' => $data]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonGetterMethodCallsProvider(): iterable
    {
        yield 'save' => ['obj.save()'];
        yield 'delete' => ['obj.delete()'];
        yield 'SAVE' => ['obj.SAVE()'];
        yield 'setSomething' => ['obj.setSomething("x")'];
    }

    /**
     * @dataProvider nonGetterMethodCallsProvider
     */
    public function testRejectsNonGetterMethodCalls(string $expression): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate($expression, ['obj' => new ExpressionLanguageTestFixture()]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function infrastructureAccessorMethodCallsProvider(): iterable
    {
        yield 'getDao' => ['obj.getDao()'];
        yield 'GETDAO' => ['obj.GETDAO()'];
        yield 'getResource' => ['obj.getResource()'];
        yield 'GetResource' => ['obj.GetResource()'];
    }

    /**
     * @dataProvider infrastructureAccessorMethodCallsProvider
     */
    public function testRejectsInfrastructureAccessorMethodCallsCaseInsensitively(string $expression): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate($expression, ['obj' => new ExpressionLanguageTestFixture()]);
    }

    public function testRejectsNonGetterMethodCallsNestedInsideAnExpression(): void
    {
        // the disallowed call does not have to be the outermost node - every method call
        // reachable by walking the parsed AST must be checked.
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('obj.isActive() ? obj.save() : null', ['obj' => new ExpressionLanguageTestFixture()]);
    }

    public function testStillRejectsConstantFunction(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('constant("PHP_VERSION")', []);
    }
}

final class ExpressionLanguageTestFixture
{
    public function getId(): int
    {
        return 42;
    }

    public function isActive(): bool
    {
        return true;
    }

    public function hasChildren(): bool
    {
        return true;
    }
}
