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

namespace Pimcore\Tests\Model\DataObject\ClassDefinition\Data;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\ModelTestCase;

class WysiwygTest extends ModelTestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    public function testGetVersionPreviewStripsBrokenLinkWithExtraAttributes(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = new Document\Page();
        $unpublishedDocument->setKey('unpublished-version-preview-testing');
        $unpublishedDocument->setPublished(false);
        $unpublishedDocument->setParentId(1);
        $unpublishedDocument->setUserOwner(1);
        $unpublishedDocument->setUserModification(1);
        $unpublishedDocument->setCreationDate(time());
        $unpublishedDocument->save();

        $data = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener">Link text</a>',
            $unpublishedDocument->getId()
        );

        $field = new Wysiwyg();
        $field->setName('description');

        $this->assertEquals('Link to a document Link text', $field->getVersionPreview($data));
    }
}
