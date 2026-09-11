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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * getVersionPreview() returns HTML, so the relation metadata it embeds has to be escaped.
 *
 * @internal
 */
class AdvancedRelationVersionPreviewTest extends TestCase
{
    private const PAYLOAD = '</span><img src=x onerror=alert(1)>';

    protected function needsDb(): bool
    {
        return false;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function relationProvider(): array
    {
        return [
            'element relation' => ['element'],
            'object relation' => ['object'],
        ];
    }

    /**
     * Built inside the test rather than in the provider: providers are static, and these need
     * mocks. The containers override the element lookup so the preview can be rendered without
     * loading anything.
     *
     * @return array{0: Data, 1: ElementMetadata|ObjectMetadata}
     */
    private function relation(string $kind): array
    {
        if ($kind === 'element') {
            $asset = $this->createMock(Asset::class);
            $asset->method('getRealFullPath')->willReturn('/assets/one.jpg');

            $metadata = new class('relation', ['comment'], $asset) extends ElementMetadata {
                public function __construct(string $fieldname, array $columns, private Asset $stub)
                {
                    parent::__construct($fieldname, $columns);
                }

                public function getElement(): ?ElementInterface
                {
                    return $this->stub;
                }
            };

            return [new AdvancedManyToManyRelation(), $metadata];
        }

        $object = $this->createMock(Concrete::class);
        $object->method('getRealFullPath')->willReturn('/objects/one');

        $metadata = new class('relation', ['comment'], $object) extends ObjectMetadata {
            public function __construct(string $fieldname, array $columns, private Concrete $stub)
            {
                parent::__construct($fieldname, $columns);
            }

            public function getObject(): ?Concrete
            {
                return $this->stub;
            }
        };

        return [new AdvancedManyToManyObjectRelation(), $metadata];
    }

    /**
     * @dataProvider relationProvider
     */
    public function testMetadataValuesAreEscaped(string $kind): void
    {
        [$fd, $metadata] = $this->relation($kind);
        $metadata->setData(['comment' => self::PAYLOAD]);

        $html = $fd->getVersionPreview([$metadata]);

        $this->assertStringNotContainsString('<img', $html, 'metadata must not reach the preview as markup');
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString(
            '<span class="preview-metadata">',
            $html,
            'the surrounding preview markup must stay intact'
        );
    }

    /**
     * The column keys are configuration rather than free text, but they are concatenated into the
     * same string, so they are escaped on the same basis.
     *
     * @dataProvider relationProvider
     */
    public function testMetadataKeysAreEscaped(string $kind): void
    {
        [$fd, $metadata] = $this->relation($kind);
        $metadata->setData([self::PAYLOAD => 'a value']);

        $html = $fd->getVersionPreview([$metadata]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }
}
