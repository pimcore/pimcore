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

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * The publish guard for required editables reports structured validation errors.
 *
 * @group model.document.document
 */
class PageSnippetValidationTest extends ModelTestCase
{
    private const MESSAGE = 'Prevented publishing document - missing values for required editables';

    public function testFlagSetFromOutsideProducesAnAggregateWithoutField(): void
    {
        $exception = $this->saveExpectingFailure($this->createPage()->setMissingRequiredEditable(true));

        $this->assertSame(self::MESSAGE, $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MISSING_REQUIRED_EDITABLES->value, $exception->getTranslationKey());
        $this->assertNull($exception->getFieldName());
        $this->assertSame([$exception], $exception->getViolations());
    }

    private function createPage(): Page
    {
        $page = new Page();
        $page->setKey('page-snippet-validation-' . uniqid());
        $page->setParentId(1);
        $page->setPublished(true);

        return $page;
    }

    private function saveExpectingFailure(Page $page): StructuredValidationException
    {
        try {
            $page->save();
        } catch (StructuredValidationException $exception) {
            return $exception;
        }

        $this->fail('Expected a StructuredValidationException');
    }
}
