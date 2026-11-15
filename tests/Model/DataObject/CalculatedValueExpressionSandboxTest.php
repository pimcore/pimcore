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
use Error;
use Pimcore;
use Pimcore\Helper\SymfonyExpression\CalculatedFieldExpressionLanguage;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\UnittestBrick;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\Note\Listing as NoteListing;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use RuntimeException;
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenExpressionProvider(): iterable
    {
        yield 'dao' => ['object.getDao().db'];
        yield 'resource' => ['object.getResource()'];
        yield 'dao via getObjectVar' => ['object.getObjectVar("dao")'];
        yield 'dao via getValueForFieldName' => ['object.getValueForFieldName("dao")'];
        yield 'dao via generic get' => ['object.get("dao")'];
        yield 'static getList with SQL condition' => ['object.getList({"condition": "1=1"})'];
        yield 'static getList via getValueFromParent' => ['object.getValueFromParent("List", {"condition": "1=1"})'];
        yield 'static getById' => ['object.getById(1)'];
        yield 'SQL via getRelationData' => ['object.getRelationData("objects", true, "1")'];
        yield 'SQL via hasSiblings' => ['object.hasSiblings(["object"])'];
        yield 'class definition' => ['object.getClass()'];
        yield 'children listing' => ['object.getChildren()'];
        yield 'save' => ['object.save()'];
        yield 'delete' => ['object.delete()'];
        yield 'setter' => ['object.setInput("changed")'];
        yield 'parent dao' => ['object.getParent().getDao()'];
        yield 'related object dao' => ['object.getHref().getDao()'];
        yield 'brick owner object' => ['object.getMybricks().getUnittestBrick().getObject()'];
        yield 'brick definition' => ['object.getMybricks().getUnittestBrick().getDefinition()'];
        yield 'brick container items setter' => ['object.getMybricks().setItems([])'];
        yield 'localized fields owner object' => ['object.getLocalizedfields().getObject()'];
        yield 'fieldcollection item definition' => ['object.getFieldcollection().get(0).getDefinition()'];
        yield 'fieldcollection item dao' => ['object.getFieldcollection().get(0).getDao()'];
        yield 'hotspot image thumbnail' => ['object.getHotspotimage().getThumbnail("x")'];
        yield 'asset thumbnail' => ['object.getHotspotimage().getImage().getThumbnail("x")'];
        yield 'asset stream' => ['object.getHotspotimage().getImage().getStream()'];
        yield 'url slug action' => ['object.getUrlSlug()[0].getAction()'];
        yield 'data key definition' => ['data.getKeyDefinition()'];
        yield 'property access' => ['object.o_dao'];
        yield 'original returned by max()' => ['max(object.getHotspotimage(), object.getHotspotimage()).getThumbnail("x")'];
        yield 'original element returned by min()' => ['min(object.getHref(), object.getHref()).getDao()'];
        yield 'direct __call to delete' => ['object.__call("delete", [])'];
        yield 'direct __call to dao' => ['object.__call("getDao", [])'];
        yield 'direct __call on a value view' => ['object.getHotspotimage().__call("getThumbnail", ["x"])'];
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

    public function testProxyCannotBeReinitialized(): void
    {
        $object = $this->createObject();

        $this->expectException(Error::class);
        $this->evaluate('object.__construct()', $object);
    }

    public function testDatesAreDetachedCopies(): void
    {
        $object = $this->createObject();
        $before = $object->getDate()->getTimestamp();

        $this->evaluate('object.getDate().modify("+1 year")', $object);
        $this->assertSame($before, $object->getDate()->getTimestamp());

        // Carbon's global state (test time, settings) is not reachable from an expression
        $this->expectException(RuntimeException::class);
        $this->evaluate('object.getDate().setTestNow()', $object);
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
        $related = TestHelper::createEmptyObject('related');

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject('', false);
        $object->setInput('sandbox');
        $object->setDate(new Carbon('2026-01-02'));
        $object->setHref($related);
        $object->setHotspotimage(new Hotspotimage(TestHelper::createImageAsset()));
        $object->setUrlSlug([new UrlSlug('/sandbox-slug')]);

        $brick = new UnittestBrick($object);
        $brick->setBrickinput('brick');
        $object->getMybricks()->setUnittestBrick($brick);

        $item = new Unittestfieldcollection();
        $item->setFieldinput1('item');
        $object->setFieldcollection(new Fieldcollection([$item]));

        $object->save();

        return $object;
    }

    private function evaluate(string $expression, Unittest $object): mixed
    {
        $data = new CalculatedValue('calculatedValueExpression');
        $data->setContextualData('object', null, null, null);

        return $this->expressionLanguage->evaluate($expression, ['object' => $object, 'data' => $data]);
    }

    private function countNotes(Unittest $object): int
    {
        $list = new NoteListing();
        $list->setCondition('cid = ? AND ctype = ?', [$object->getId(), 'object']);

        return $list->getTotalCount();
    }
}
