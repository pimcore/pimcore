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

use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Textarea;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Tests for the content-main document inheritance mechanism.
 *
 * Regression coverage for the bug where editables inherited from the
 * content-main document were saved onto the child document on the first
 * Save & Publish, causing inherited content to disappear on subsequent
 * page loads.
 *
 * @group model.document.document
 */
class ContentMainDocumentInheritanceTest extends ModelTestCase
{
    private ?Page $mainDocument = null;

    private ?Page $childDocument = null;

    private bool $previousGetInheritedValues = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousGetInheritedValues = PageSnippet::getGetInheritedValues();
        PageSnippet::setGetInheritedValues(true);
    }

    protected function tearDown(): void
    {
        PageSnippet::setGetInheritedValues($this->previousGetInheritedValues);

        try {
            $this->childDocument?->delete();
        } catch (\Exception) {
        }
        try {
            $this->mainDocument?->delete();
        } catch (\Exception) {
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeInputEditable(string $name, string $value): Input
    {
        $editable = new Input();
        $editable->setName($name);
        $editable->setDataFromResource($value);

        return $editable;
    }

    private function reloadMain(): void
    {
        $this->mainDocument = Page::getById($this->mainDocument->getId(), ['force' => true]);
    }

    private function reloadChild(): void
    {
        $this->childDocument = Page::getById($this->childDocument->getId(), ['force' => true]);
    }

    /**
     * Returns the child document's own editables as stored in the DB,
     * bypassing content-main inheritance entirely.
     *
     * getEditable() always falls through to the content-main document lookup
     * even when getGetInheritedValues() is false, so it cannot be used to
     * distinguish "own" from "inherited" without this helper.
     *
     * @return array<string, \Pimcore\Model\Document\Editable>
     */
    private function getRawChildEditables(): array
    {
        $previous = PageSnippet::getGetInheritedValues();
        PageSnippet::setGetInheritedValues(false);

        try {
            $rawChild = Page::getById($this->childDocument->getId(), ['force' => true]);
            // getEditables() without InheritedValues only populates from DAO (DB).
            return $rawChild->getEditables();
        } finally {
            PageSnippet::setGetInheritedValues($previous);
        }
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Verifies that inherited editables are NOT persisted onto the child
     * document when it is saved.
     *
     * Reproduces the bug fixed in getEditables(): without the
     * clone+setInherited(true) step, array_merge() would include the
     * content-main editables in the map returned to the DAO's update()
     * loop, which then wrote them to the child's own record.  After that
     * first save the child owned a stale/empty copy of the editable that
     * shadowed the parent's value forever.
     */
    public function testInheritedEditablesAreNotPersistedOnChildAfterSave(): void
    {
        // 1. Create the main document with a known editable value.
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('headline', 'Hello from main'));
        $this->mainDocument->save();

        // 2. Create the child and wire it to the main document.
        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        $this->childDocument->save();

        // 3. Verify inheritance works before any subsequent save.
        $this->reloadChild();
        $editable = $this->childDocument->getEditable('headline');
        $this->assertNotNull($editable, 'Editable should be inherited before second save');
        $this->assertEquals('Hello from main', $editable->getData(), 'Child should show main document value before second save');
        $this->assertTrue($editable->getInherited(), 'Editable returned via getEditable() must be flagged as inherited');

        // 4. Save the child document a second time (this is what triggered the bug).
        $this->childDocument->save();

        // 5. Reload both documents fresh from DB, simulating a new request.
        $this->reloadChild();
        $this->reloadMain();

        // 6. The child must still show the inherited value, not a blank one.
        $editableAfterSecondSave = $this->childDocument->getEditable('headline');
        $this->assertNotNull($editableAfterSecondSave, 'Editable must still be visible after second save of child');
        $this->assertEquals(
            'Hello from main',
            $editableAfterSecondSave->getData(),
            'Inherited value must survive a second save of the child document'
        );

        // 7. The child must NOT have its own persisted copy of the editable.
        //    Use getEditables() with inheritance disabled to inspect only the
        //    child's own DB record (getEditable() always falls back to the
        //    content-main document, so it cannot be used here).
        $ownEditables = $this->getRawChildEditables();
        $this->assertArrayNotHasKey(
            'headline',
            $ownEditables,
            'Child document must NOT own its own copy of the inherited editable after save'
        );
    }

    /**
     * Verifies the inherited flag on editables returned by getEditables().
     *
     * Every editable present in the merged map that originates from the
     * content-main document must be flagged as inherited so downstream
     * consumers (DAO, renderers) can distinguish owned vs. inherited data.
     */
    public function testGetEditablesMarksContentMainEditablesAsInherited(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('title', 'Main Title'));
        $this->mainDocument->save();

        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        $this->childDocument->save();

        $this->reloadChild();

        $editables = $this->childDocument->getEditables();
        $this->assertArrayHasKey('title', $editables, 'Merged map must contain the content-main editable');
        $this->assertTrue(
            $editables['title']->getInherited(),
            'Content-main editable in getEditables() map must be flagged as inherited'
        );
    }

    /**
     * Verifies that a child's own editable overrides the content-main value
     * and is correctly persisted without affecting the main document.
     */
    public function testChildOwnEditableOverridesContentMain(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('headline', 'Main value'));
        $this->mainDocument->save();

        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        // Child explicitly overrides the same editable.
        $this->childDocument->setEditable($this->makeInputEditable('headline', 'Child override'));
        $this->childDocument->save();

        $this->reloadChild();
        $editable = $this->childDocument->getEditable('headline');
        $this->assertNotNull($editable);
        $this->assertEquals('Child override', $editable->getData(), 'Child own value must take precedence over content-main');
        $this->assertFalse($editable->getInherited(), 'Child own editable must NOT be flagged as inherited');

        // Main document must be untouched.
        $this->reloadMain();
        $mainEditable = $this->mainDocument->getEditable('headline');
        $this->assertEquals('Main value', $mainEditable->getData(), 'Main document editable must remain unchanged');
    }

    /**
     * Verifies that updates to the main document are reflected in the child
     * after subsequent saves of the child — i.e. the fix does not prevent
     * live inheritance from working.
     */
    public function testUpdatedMainDocumentValueIsReflectedInChild(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('teaser', 'Original teaser'));
        $this->mainDocument->save();

        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        $this->childDocument->save();

        // Save child a second time (the formerly broken path).
        $this->childDocument->save();

        // Now update the main document.
        $this->reloadMain();
        $this->mainDocument->setEditable($this->makeInputEditable('teaser', 'Updated teaser'));
        $this->mainDocument->save();

        // Reload child fresh and check it sees the updated value.
        $this->reloadChild();
        $editable = $this->childDocument->getEditable('teaser');
        $this->assertNotNull($editable, 'Editable must still be visible after main document update');
        $this->assertEquals(
            'Updated teaser',
            $editable->getData(),
            'Child must reflect updated value from main document after fix'
        );
    }

    /**
     * Verifies that multiple editables are all correctly inherited and none
     * are accidentally persisted onto the child document.
     */
    public function testMultipleEditablesInheritedWithoutPersistingOnChild(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('title', 'Page Title'));
        $textarea = new Textarea();
        $textarea->setName('body');
        $textarea->setDataFromResource('Page body text');
        $this->mainDocument->setEditable($textarea);
        $this->mainDocument->save();

        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        $this->childDocument->save();

        // Two saves to reliably trigger the former bug.
        $this->childDocument->save();

        $this->reloadChild();

        $titleEditable = $this->childDocument->getEditable('title');
        $this->assertNotNull($titleEditable);
        $this->assertEquals('Page Title', $titleEditable->getData());
        $this->assertTrue($titleEditable->getInherited());

        $bodyEditable = $this->childDocument->getEditable('body');
        $this->assertNotNull($bodyEditable);
        $this->assertEquals('Page body text', $bodyEditable->getData());
        $this->assertTrue($bodyEditable->getInherited());

        // Inspect child's own DB record: must own neither editable.
        $ownEditables = $this->getRawChildEditables();
        $this->assertArrayNotHasKey('title', $ownEditables, 'title must not be persisted on child');
        $this->assertArrayNotHasKey('body', $ownEditables, 'body must not be persisted on child');
    }

    /**
     * Verifies that cloning an inherited editable inside getEditables() does
     * not mutate the parent document's own in-memory editable object.
     */
    public function testContentMainDocumentEditableObjectIsNotMutated(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('intro', 'Intro text'));
        $this->mainDocument->save();

        // Access main document's editable directly — it must NOT be inherited.
        $mainEditable = $this->mainDocument->getEditable('intro');
        $this->assertNotNull($mainEditable);
        $this->assertFalse($mainEditable->getInherited(), 'Main document own editable must never be flagged as inherited');

        $this->childDocument = TestHelper::createEmptyDocumentPage();
        $this->childDocument->setContentMainDocumentId($this->mainDocument->getId(), true);
        $this->childDocument->save();

        // Trigger the merge via getEditables() on a freshly loaded child.
        $this->reloadChild();
        $this->childDocument->getEditables();

        // Re-check the main document's own editable: must still not be inherited.
        // (If clone was missing, setInherited(true) would have mutated the main's object.)
        $mainEditableAfterChildLoad = $this->mainDocument->getEditable('intro');
        $this->assertFalse(
            $mainEditableAfterChildLoad->getInherited(),
            'Main document editable must not be mutated (setInherited) by child getEditables() call'
        );
    }

    /**
     * Verifies behaviour when a document has NO content-main document set:
     * getEditables() must return only its own editables, none flagged inherited.
     */
    public function testGetEditablesWithoutContentMainDocumentIsUnaffected(): void
    {
        $this->mainDocument = TestHelper::createEmptyDocumentPage();
        $this->mainDocument->setEditable($this->makeInputEditable('standalone', 'standalone value'));
        $this->mainDocument->save();

        $this->reloadMain();
        $editables = $this->mainDocument->getEditables();
        $this->assertArrayHasKey('standalone', $editables);
        $this->assertFalse(
            $editables['standalone']->getInherited(),
            'An editable on a document with no content-main must not be marked inherited'
        );
    }
}
