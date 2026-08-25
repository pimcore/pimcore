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

use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * getVersionPreview() returns HTML, so the relation metadata it embeds has to be escaped.
 *
 * @internal
 */
class AdvancedRelationVersionPreviewTest extends TestCase
{
    protected function needsDb(): bool
    {
        return false;
    }

    public function testMetadataIsEscapedInTheVersionPreview(): void
    {
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
        $metadata->setData(['comment' => '</span><img src=x onerror=alert(1)>']);

        $html = (new AdvancedManyToManyObjectRelation())->getVersionPreview([$metadata]);

        $this->assertStringNotContainsString('<img', $html, 'metadata must not reach the preview as markup');
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString(
            '<span class="preview-metadata">',
            $html,
            'the surrounding preview markup must stay intact'
        );
    }
}
