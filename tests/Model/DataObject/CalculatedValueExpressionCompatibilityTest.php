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

use Carbon\Carbon;
use Pimcore;
use Pimcore\Helper\SymfonyExpression\CalculatedFieldExpressionLanguage;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\UnittestBrick;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Calculated value expressions only see a read-only view of the object graph
 * (GHSA-93jv-p24f-gcpp). The getter chains existing expressions use must keep working on it.
 *
 * @group model.dataobject.calculatedvalue
 */
class CalculatedValueExpressionCompatibilityTest extends ModelTestCase
{
    private CalculatedFieldExpressionLanguage $expressionLanguage;

    private Unittest $object;

    private Unittest $related;

    private Unit $unit;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->expressionLanguage = Pimcore::getContainer()->get('pimcore.calculated_value.expression_language');
        $this->object = $this->createObject();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        $this->unit->delete();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function expressionProvider(): iterable
    {
        yield 'field getter' => ['object.getInput()', 'compat'];
        yield 'concatenation' => ['object.getInput() ~ " " ~ object.getKey()', 'compat compat-object'];
        yield 'ternary' => ['object.getInput() != "" ? "yes" : "no"', 'yes'];
        yield 'localized getter' => ['object.getLinput("en")', 'english'];
        yield 'localized container' => ['object.getLocalizedfields().getLocalizedValue("linput", "en")', 'english'];
        yield 'system getters' => ['object.getKey() ~ object.getFullPath()', 'compat-object/compat-object'];
        yield 'published' => ['object.isPublished()', true];
        yield 'parent' => ['object.getParent().getId()', 1];
        yield 'null-safe' => ['object.getLazyHref()?.getKey()', null];
        yield 'date format' => ['object.getDate().format("Y")', '2026'];
        yield 'date timestamp' => ['object.getDate().getTimestamp()', 1767312000];
        yield 'quantity value' => ['object.getQuantityValue().getValue()', 10.0];
        yield 'quantity value unit' => ['object.getQuantityValue().getUnit().getAbbreviation()', 'cmx'];
        yield 'quantity value as string' => ['object.getQuantityValue() ~ ""', '10cmx'];
        yield 'link href' => ['object.getLink().getHref()', 'https://example.com'];
        yield 'link text' => ['object.getLink().getText()', 'Example'];
        yield 'rgba color' => ['object.getRgbaColor().getR()', 255];
        yield 'rgba color hex' => ['object.getRgbaColor().getHex()', '#ff0000'];
        yield 'geopoint' => ['object.getPoint().getLatitude()', 48.2];
        yield 'geobounds' => ['object.getBounds().getNorthEast().getLongitude()', 16.4];
        yield 'many-to-one relation' => ['object.getHref().getKey()', 'compat-related'];
        yield 'relation field getter' => ['object.getHref().getInput()', 'related input'];
        yield 'many-to-many relation' => ['object.getObjects()[0].getKey()', 'compat-related'];
        yield 'relation metadata element' => ['object.getObjectswithmetadata()[0].getElement().getKey()', 'compat-related'];
        yield 'relation metadata object' => ['object.getObjectswithmetadata()[0].getObject().getKey()', 'compat-related'];
        yield 'relation metadata column' => ['object.getObjectswithmetadata()[0].getMeta1()', 'm1'];
        yield 'multiselect' => ['"b" in object.getMultiselect()', true];
        yield 'structured table' => ['object.getStructuredtable().getRow1__col2()', 'cell'];
        yield 'url slug' => ['object.getUrlSlug()[0].getSlug()', '/compat-slug'];
        yield 'hotspot image' => ['object.getHotspotimage().getImage().getFilename() != ""', true];
        yield 'object brick' => ['object.getMybricks().getUnittestBrick().getBrickinput()', 'brick'];
        yield 'object brick type' => ['object.getMybricks().getUnittestBrick().getType()', 'unittestBrick'];
        yield 'fieldcollection item' => ['object.getFieldcollection().get(0).getFieldinput1()', 'item'];
        yield 'fieldcollection items' => ['object.getFieldcollection().getItems()[0].getFieldinput1()', 'item'];
        yield 'calculated value data' => ['data.getFieldname()', 'calculatedValueExpression'];
        yield 'min/max functions' => ['max(1, 2)', 2];
    }

    /**
     * @dataProvider expressionProvider
     */
    public function testExpression(string $expression, mixed $expected): void
    {
        $this->assertSame($expected, $this->evaluate($this->expressionLanguage, $expression));
    }

    public function testCustomFunctionsReceiveTheRealObject(): void
    {
        $provider = new class() implements ExpressionFunctionProviderInterface {
            public function getFunctions(): array
            {
                return [
                    new ExpressionFunction(
                        'receivedClass',
                        static fn (): string => '',
                        static fn (array $variables, mixed $value): string => get_debug_type($value)
                            . '|' . get_debug_type($variables['object'])
                    ),
                ];
            }
        };

        $expressionLanguage = new CalculatedFieldExpressionLanguage([$provider]);

        $this->assertSame(
            Unittest::class . '|' . Unittest::class,
            $this->evaluate($expressionLanguage, 'receivedClass(object)')
        );
        $this->assertSame(
            Unittest::class . '|' . Unittest::class,
            $this->evaluate($expressionLanguage, 'receivedClass(object.getHref())')
        );
        $this->assertSame(
            QuantityValue::class . '|' . Unittest::class,
            $this->evaluate($expressionLanguage, 'receivedClass(object.getQuantityValue())')
        );
    }

    public function testFieldGetterOverriddenByAnApplicationModel(): void
    {
        $object = new class() extends Unittest {
            public function getInput(): ?string
            {
                return 'overridden:' . parent::getInput();
            }
        };
        $object->setInput('compat');

        $this->assertSame(
            'overridden:compat',
            $this->expressionLanguage->evaluate('object.getInput()', ['object' => $object, 'data' => null])
        );
    }

    public function testCalculatedFieldGetterUsesTheExpression(): void
    {
        $this->object->setFirstname('First');

        // calculatedValueExpression is defined as: object.getFirstname() ~ ' some calc'
        $this->assertSame('First some calc', $this->object->getCalculatedValueExpression());
    }

    private function createObject(): Unittest
    {
        $this->unit = Unit::create(['abbreviation' => 'cmx', 'longname' => 'compat centimeter']);
        $this->unit->save();

        /** @var Unittest $related */
        $related = TestHelper::createEmptyObject('', false);
        $related->setKey('compat-related');
        $related->setInput('related input');
        $related->save();
        $this->related = $related;

        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('https://example.com');
        $link->setText('Example');

        $metadata = new ObjectMetadata('objectswithmetadata', ['meta1', 'meta2'], $related);
        $metadata->setMeta1('m1');

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject('', false);
        $object->setKey('compat-object');
        $object->setInput('compat');
        $object->setLinput('english', 'en');
        $object->setDate(new Carbon('2026-01-02', 'UTC'));
        $object->setQuantityValue(new QuantityValue(10, $this->unit->getId()));
        $object->setLink($link);
        $object->setRgbaColor(new RgbaColor(255, 0, 0, 255));
        $object->setPoint(new GeoCoordinates(48.2, 16.3));
        $object->setBounds(new Geobounds(new GeoCoordinates(48.3, 16.4), new GeoCoordinates(48.1, 16.2)));
        $object->setHref($related);
        $object->setObjects([$related]);
        $object->setObjectswithmetadata([$metadata]);
        $object->setMultiselect(['a', 'b']);
        $object->setStructuredtable(new StructuredTable([
            'row1' => ['col1' => 1, 'col2' => 'cell'],
            'row2' => ['col1' => 2, 'col2' => ''],
            'row3' => ['col1' => 3, 'col2' => ''],
        ]));
        $object->setUrlSlug([new UrlSlug('/compat-slug')]);
        $object->setHotspotimage(new Hotspotimage(TestHelper::createImageAsset()));

        $brick = new UnittestBrick($object);
        $brick->setBrickinput('brick');
        $object->getMybricks()->setUnittestBrick($brick);

        $item = new Unittestfieldcollection();
        $item->setFieldinput1('item');
        $object->setFieldcollection(new Fieldcollection([$item]));

        $object->save();

        return $object;
    }

    private function evaluate(CalculatedFieldExpressionLanguage $expressionLanguage, string $expression): mixed
    {
        $data = new CalculatedValue('calculatedValueExpression');
        $data->setContextualData('object', null, null, null);

        return $expressionLanguage->evaluate($expression, ['object' => $this->object, 'data' => $data]);
    }
}
