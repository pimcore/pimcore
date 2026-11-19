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
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\unittestBlock;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Block values reach an expression as read-only views of their block elements
 * (GHSA-93jv-p24f-gcpp): the data of an element can be read, nothing else can be called.
 *
 * @group model.dataobject.calculatedvalue
 */
class CalculatedValueExpressionBlockTest extends ModelTestCase
{
    private CalculatedFieldExpressionLanguage $expressionLanguage;

    private unittestBlock $object;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->expressionLanguage = Pimcore::getContainer()->get('pimcore.calculated_value.expression_language');

        $object = new unittestBlock();
        $object->setParent(Service::createFolderByPath('/blocks'));
        $object->setKey('expression-block');
        $object->setPublished(true);
        $object->setTestblock([[
            'blockinput' => new BlockElement('blockinput', 'input', 'block text'),
            'blockhotspotimage' => new BlockElement(
                'blockhotspotimage',
                'hotspotimage',
                new Hotspotimage(TestHelper::createImageAsset())
            ),
        ]]);
        $object->save();
        $this->object = $object;
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_Block();
    }

    public function testBlockDataCanBeRead(): void
    {
        $this->assertSame('block text', $this->evaluate('object.getTestblock()[0]["blockinput"].getData()'));
        $this->assertSame('blockinput', $this->evaluate('object.getTestblock()[0]["blockinput"].getName()'));
        $this->assertTrue($this->evaluate(
            'object.getTestblock()[0]["blockhotspotimage"].getData().getImage().getFilename() != ""'
        ));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenBlockExpressionProvider(): iterable
    {
        yield 'block element setter' => ['object.getTestblock()[0]["blockinput"].setData("changed")'];
        yield 'hotspot thumbnail' => ['object.getTestblock()[0]["blockhotspotimage"].getData().getThumbnail("x")'];
        yield 'asset dao' => ['object.getTestblock()[0]["blockhotspotimage"].getData().getImage().getDao()'];
        yield 'block item write' => ['object.getTestblock()[0].offsetSet("blockinput", null)'];
    }

    /**
     * @dataProvider forbiddenBlockExpressionProvider
     */
    public function testForbiddenBlockExpressions(string $expression): void
    {
        $this->expectException(SyntaxError::class);
        $this->evaluate($expression);
    }

    private function evaluate(string $expression): mixed
    {
        return $this->expressionLanguage->evaluate($expression, ['object' => $this->object, 'data' => null]);
    }
}
