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
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;

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

    public function testKnownEditablesBecomeViolationsWithTheirField(): void
    {
        $page = $this->createPage()->setMissingRequiredEditable(true);
        $this->setMissingNames($page, ['headline', 'teaser']);

        $exception = $this->saveExpectingFailure($page);

        $this->assertSame(self::MESSAGE, $exception->getMessage());
        $this->assertNull($exception->getTranslationKey());
        $violations = $exception->getViolations();
        $this->assertCount(2, $violations);
        $this->assertSame(['headline', 'teaser'], array_map(
            static fn (StructuredValidationException $violation) => $violation->getFieldName(),
            $violations
        ));
        $this->assertSame('Missing value for required editable [ headline ]', $violations[0]->getMessage());
        $this->assertSame(
            ValidationMessageKey::MISSING_REQUIRED_EDITABLE->value,
            $violations[0]->getTranslationKey()
        );
    }

    public function testChangingTheFlagResetsCollectedNames(): void
    {
        $page = $this->createPage()->setMissingRequiredEditable(true);
        $this->setMissingNames($page, ['headline']);

        // an unchanged flag keeps the names
        $page->setMissingRequiredEditable(true);
        $this->assertSame(['headline'], $this->getMissingNames($page));

        // a changed flag (also the reset to null) drops the names of the earlier check
        $page->setMissingRequiredEditable(null);
        $this->assertSame([], $this->getMissingNames($page));

        $this->setMissingNames($page, ['headline']);
        $page->setMissingRequiredEditable(true);
        $this->assertSame([], $this->getMissingNames($page));

        $exception = $this->saveExpectingFailure($page);
        $this->assertSame([$exception], $exception->getViolations(), 'no stale editable is reported');
        $this->assertNull($exception->getFieldName());
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

    /**
     * @param list<string> $names
     */
    private function setMissingNames(Page $page, array $names): void
    {
        // the names are only collected while rendering the document: there is no public seam to seed them
        (new ReflectionProperty(PageSnippet::class, 'missingRequiredEditableNames'))->setValue($page, $names);
    }

    /**
     * @return list<string>
     */
    private function getMissingNames(Page $page): array
    {
        return (new ReflectionProperty(PageSnippet::class, 'missingRequiredEditableNames'))->getValue($page);
    }
}
