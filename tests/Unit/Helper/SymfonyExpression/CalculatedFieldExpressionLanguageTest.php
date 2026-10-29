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

use Carbon\Carbon;
use DateTime;
use LogicException;
use PHPUnit\Framework\TestCase;
use Pimcore\Helper\SymfonyExpression\CalculatedFieldExpressionLanguage;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Fieldcollection;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @internal
 */
final class CalculatedFieldExpressionLanguageTest extends TestCase
{
    public function testAllowsGetPrefixedMethodCallsOnDataValueObjects(): void
    {
        $language = new CalculatedFieldExpressionLanguage();
        $data = new CalculatedValue('myField');

        $this->assertSame('myField', $language->evaluate('data.getFieldname()', ['data' => $data]));
    }

    public function testAllowsIsAndHasPrefixedMethodCalls(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->assertTrue($language->evaluate('date.isLeapYear()', ['date' => Carbon::create(2024)]));
        $this->assertFalse($language->evaluate('gallery.hasValidImages()', ['gallery' => new ImageGallery([])]));
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

    public function testAllowsNullSafeMethodCallChains(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->assertNull($language->evaluate('date?.getTimestamp()', ['date' => null]));
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
        $language->evaluate($expression, ['obj' => new CalculatedValue('myField')]);
    }

    /**
     * Getter-named methods that hand out infrastructure, read arbitrary properties or methods
     * by name, or pass their arguments into SQL.
     *
     * @return iterable<string, array{string}>
     */
    public static function forbiddenGetterMethodCallsProvider(): iterable
    {
        yield 'getDao' => ['obj.getDao()'];
        yield 'GETDAO' => ['obj.GETDAO()'];
        yield 'getResource' => ['obj.getResource()'];
        yield 'GetResource' => ['obj.GetResource()'];
        yield 'getObjectVar' => ['obj.getObjectVar("dao")'];
        yield 'getObjectVars' => ['obj.getObjectVars()'];
        yield 'getValueForFieldName' => ['obj.getValueForFieldName("dao")'];
        yield 'getValueFromParent' => ['obj.getValueFromParent("List")'];
        yield 'getRelationData' => ['obj.getRelationData("field", true, "1")'];
        yield 'hasSiblings' => ['obj.hasSiblings(["object"])'];
    }

    /**
     * @dataProvider forbiddenGetterMethodCallsProvider
     */
    public function testRejectsForbiddenGetterMethodCallsCaseInsensitively(string $expression): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate($expression, ['obj' => new CalculatedValue('myField')]);
    }

    public function testRejectsThumbnailGenerationFromExpressionSuppliedConfig(): void
    {
        // the thumbnail configuration (e.g. an overlay file path) would come from the expression
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('image.getThumbnail({"items": []}) ~ ""', ['image' => new Hotspotimage()]);
    }

    public function testRejectsUrlSlugActionLookup(): void
    {
        // UrlSlug::getAction() deletes the slug when its field definition can't be resolved
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('slug.getAction()', ['slug' => new UrlSlug('/stale-slug')]);
    }

    public function testRejectsNonGetterMethodCallsNestedInsideAnExpression(): void
    {
        // the disallowed call does not have to be the outermost node - every method call
        // reachable by walking the parsed AST must be checked.
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('obj.getFieldname() ? obj.save() : null', ['obj' => new CalculatedValue('myField')]);
    }

    public function testRejectsPropertyAccess(): void
    {
        // property access reaches public properties such as AbstractDao::$db without any method call
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $language->evaluate('obj.db', ['obj' => new ExpressionLanguageTestFixture()]);
    }

    public function testRejectsMethodCallsOnReceiversOutsideTheAllowList(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('not allowed');
        $language->evaluate('obj.getId()', ['obj' => new ExpressionLanguageTestFixture()]);
    }

    public function testRejectsStaticMethodCallsOnAllowedReceivers(): void
    {
        // static methods are callable on an instance in PHP (e.g. DataObject::getList()),
        // DateTime::getLastErrors() is a static getter on an allowed receiver type
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('not allowed');
        $language->evaluate('date.getLastErrors()', ['date' => new DateTime()]);
    }

    public function testAllowsMagicColumnGettersOfDataOnlyValueObjects(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $table = new StructuredTable(['row1' => ['col1' => 'cell']]);
        $this->assertSame('cell', $language->evaluate('table.getRow1__col1()', ['table' => $table]));

        $objectMetadata = new ObjectMetadata('relation', ['text']);
        $objectMetadata->setText('object-meta');
        $this->assertSame('object-meta', $language->evaluate('meta.getText()', ['meta' => $objectMetadata]));

        $elementMetadata = new ElementMetadata('relation', ['text']);
        $elementMetadata->setText('element-meta');
        $this->assertSame('element-meta', $language->evaluate('meta.getText()', ['meta' => $elementMetadata]));
    }

    public function testAllowsFieldcollectionItemGetter(): void
    {
        $language = new CalculatedFieldExpressionLanguage();

        $this->assertNull($language->evaluate('collection.get(0)', ['collection' => new Fieldcollection()]));
    }

    public function testRejectsGetOnFieldcollectionItems(): void
    {
        // Fieldcollection\Data\AbstractData::get() dispatches arbitrary getter names
        $language = new CalculatedFieldExpressionLanguage();
        $item = new class() extends Fieldcollection\Data\AbstractData {
        };

        $this->expectException(SyntaxError::class);
        $language->evaluate('item.get("dao")', ['item' => $item]);
    }

    public function testAllowsClassificationstoreGroupAndKeyGetterChains(): void
    {
        $language = new CalculatedFieldExpressionLanguage();
        $store = new Classificationstore();
        $key = new Classificationstore\Key(
            new Classificationstore\Group($store, new Classificationstore\GroupConfig()),
            new Classificationstore\KeyConfig()
        );

        $this->assertSame($store, $language->evaluate('key.getGroup().getClassificationStore()', ['key' => $key]));
    }

    public function testCompilingIsNotSupported(): void
    {
        // compiled expressions would bypass the runtime receiver checks
        $language = new CalculatedFieldExpressionLanguage();

        $this->expectException(LogicException::class);
        $language->compile('data.getFieldname()', ['data']);
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
    public string $db = 'connection';

    public function getId(): int
    {
        return 42;
    }
}
