<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Analytics\Code;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Code\CodeCollector;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Tests\Test\TestCase;

class CodeCollectorTest extends TestCase
{
    private $validBlocks = ['A', 'B'];
    private $defaultBlock = 'A';

    /**
     * @var CodeCollector
     */
    private $collector;

    protected function setUp()
    {
        parent::setUp();

        $this->collector = new CodeCollector($this->validBlocks, $this->defaultBlock);
    }

    private function getCodeParts(): array
    {
        $reflector = new \ReflectionClass(CodeCollector::class);

        $property = $reflector->getProperty('codeParts');
        $property->setAccessible(true);

        $value = $property->getValue($this->collector);

        $property->setAccessible(false);

        return $value;
    }

    private function buildSiteId(string $configKey): SiteId
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|SiteId $stub */
        $stub = $this
            ->getMockBuilder(SiteId::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stub
            ->method('getConfigKey')
            ->willReturn($configKey);

        return $stub;
    }

    public function testCodeIsAddedToDefaultBlock()
    {
        $this->assertEmpty($this->getCodeParts());

        $this->collector->addCodePart('foo');

        $this->assertEquals([
            CodeCollector::CONFIG_KEY_GLOBAL => [
                'A' => [
                    CodeCollector::ACTION_APPEND => [
                        'foo'
                    ]
                ]
            ]
        ], $this->getCodeParts());
    }

    public function testCodeIsAddedToSelectedBlock()
    {
        $this->assertEmpty($this->getCodeParts());

        $this->collector->addCodePart('foo', 'B');

        $this->assertEquals([
            CodeCollector::CONFIG_KEY_GLOBAL => [
                'B' => [
                    CodeCollector::ACTION_APPEND => [
                        'foo'
                    ]
                ]
            ]
        ], $this->getCodeParts());
    }

    public function testCodeIsAddedToSelectedAction()
    {
        $this->assertEmpty($this->getCodeParts());

        $this->collector->addCodePart('foo', 'A', CodeCollector::ACTION_PREPEND);

        $this->assertEquals([
            CodeCollector::CONFIG_KEY_GLOBAL => [
                'A' => [
                    CodeCollector::ACTION_PREPEND => [
                        'foo'
                    ]
                ]
            ]
        ], $this->getCodeParts());
    }

    public function testCodeIsAddedToSiteId()
    {
        $siteId = $this->buildSiteId('site_1');

        $this->collector->addCodePart('foo', 'A', CodeCollector::ACTION_APPEND, $siteId);

        $this->assertEquals([
            'site_1' => [
                'A' => [
                    CodeCollector::ACTION_APPEND => [
                        'foo'
                    ]
                ]
            ]
        ], $this->getCodeParts());
    }

    public function testEnrichCodeBlock()
    {
        $siteId = $this->buildSiteId('site_1');

        $this->collector->addCodePart(':APPEND', 'A');
        $this->collector->addCodePart('PREPEND:', 'A', CodeCollector::ACTION_PREPEND);

        $codeBlock = new CodeBlock(['code']);

        $this->assertEquals('code', $codeBlock->asString());

        $this->collector->enrichCodeBlock($siteId, $codeBlock, 'A');

        $this->assertEquals("PREPEND:\ncode\n:APPEND", $codeBlock->asString());
    }

    public function testEnrichCodeBlockHandleSiteSpecificParts()
    {
        $siteIdA = $this->buildSiteId('site_a');
        $siteIdB = $this->buildSiteId('site_b');

        // only added to A
        $this->collector->addCodePart(':SITE A SPECIFIC', 'A', CodeCollector::ACTION_APPEND, $siteIdA);

        // only added to B
        $this->collector->addCodePart(':SITE B SPECIFIC', 'A', CodeCollector::ACTION_APPEND, $siteIdB);

        // added to both
        $this->collector->addCodePart('GLOBAL:', 'A', CodeCollector::ACTION_PREPEND);

        $codeBlockA = new CodeBlock(['codeA']);
        $this->assertEquals('codeA', $codeBlockA->asString());

        $this->collector->enrichCodeBlock($siteIdA, $codeBlockA, 'A');
        $this->assertEquals("GLOBAL:\ncodeA\n:SITE A SPECIFIC", $codeBlockA->asString());

        $codeBlockB = new CodeBlock(['codeB']);
        $this->assertEquals('codeB', $codeBlockB->asString());

        $this->collector->enrichCodeBlock($siteIdB, $codeBlockB, 'A');
        $this->assertEquals("GLOBAL:\ncodeB\n:SITE B SPECIFIC", $codeBlockB->asString());
    }

    public function testDefaultBlockIsInValidBlocks()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The default block "C" must be a part of the valid blocks');

        new CodeCollector($this->validBlocks, 'C');
    }

    public function testErrorOnInvalidBlock()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid block "C". Valid values are: A, B');

        $this->collector->addCodePart('foo', 'C');
    }

    public function testErrorOnInvalidAction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid action "merge". Valid actions are: prepend, append');

        $this->collector->addCodePart('foo', 'A', 'merge');
    }
}
