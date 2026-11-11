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

namespace Pimcore\Tests\Model\Element;

use Pimcore\Db;
use Pimcore\Model\Element\Tag;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;

/**
 * @group model.element.tag
 */
class TagHasChildrenTest extends ModelTestCase
{
    private const PREFIX = 'hasChildrenTest_';

    protected function setUp(): void
    {
        parent::setUp();
        $this->removeTestTags();
    }

    protected function tearDown(): void
    {
        $this->removeTestTags();
        parent::tearDown();
    }

    private function removeTestTags(): void
    {
        $listing = new Tag\Listing();
        $listing->setCondition('name LIKE ?', [addcslashes(self::PREFIX, '_%\\') . '%']);
        // children first, so no parent is deleted twice
        $tags = $listing->load();
        usort($tags, static fn (Tag $a, Tag $b): int => strlen($b->getIdPath()) <=> strlen($a->getIdPath()));
        foreach ($tags as $tag) {
            if (Tag::getById((int) $tag->getId())) {
                $tag->delete();
            }
        }
    }

    private function createTag(string $name, int $parentId = 0): Tag
    {
        $tag = new Tag();
        $tag->setName(self::PREFIX . $name);
        $tag->setParentId($parentId);
        $tag->save();

        return $tag;
    }

    private function selectCount(): int
    {
        return (int) Db::get()->fetchAssociative("SHOW SESSION STATUS LIKE 'Com_select'")['Value'];
    }

    /**
     * @return Tag[] the tags of the test, indexed by name without prefix
     */
    private function loadTestTags(): array
    {
        $listing = new Tag\Listing();
        $listing->setCondition('name LIKE ?', [self::PREFIX . '%']);
        $listing->setOrderKey('name');

        $tags = [];
        foreach ($listing->load() as $tag) {
            $tags[substr($tag->getName(), strlen(self::PREFIX))] = $tag;
        }

        return $tags;
    }

    public function testHasChildrenOverListingIsCorrectAndRunsOneQuery(): void
    {
        $withChildren = [];
        for ($i = 1; $i <= 3; $i++) {
            $parent = $this->createTag("parent$i");
            $withChildren["parent$i"] = true;
            for ($k = 1; $k <= 5; $k++) {
                $this->createTag("child{$i}_$k", (int) $parent->getId());
            }
        }
        $this->createTag('leaf');

        $tags = $this->loadTestTags();
        $this->assertCount(19, $tags);

        $before = $this->selectCount();
        $result = [];
        foreach ($tags as $name => $tag) {
            $result[$name] = $tag->hasChildren();
        }
        $selects = $this->selectCount() - $before;

        foreach ($result as $name => $hasChildren) {
            $this->assertSame(isset($withChildren[$name]), $hasChildren, "hasChildren() of $name");
        }
        $this->assertLessThanOrEqual(1, $selects, 'hasChildren() over 19 listed tags must not run one query per tag');
    }

    public function testHasChildrenSeesTagsSavedAndDeletedAfterTheListingWasLoaded(): void
    {
        $parent = $this->createTag('parent');
        $leaf = $this->createTag('leaf');

        $tags = $this->loadTestTags();
        $this->assertFalse($tags['leaf']->hasChildren());

        // a child is added to a tag of the already queried listing
        $child = $this->createTag('child', (int) $leaf->getId());
        $this->assertTrue($tags['leaf']->hasChildren());

        // and removed again
        $child->delete();
        $this->assertFalse($tags['leaf']->hasChildren());
        $this->assertFalse($parent->hasChildren());
    }

    public function testHasChildrenSeesADeleteAfterTheLookupWasBuilt(): void
    {
        $parent = $this->createTag('parent');
        $child = $this->createTag('child', (int) $parent->getId());

        // all saves are done; the lookup is built after the last one, so only delete() can outdate it
        $tags = $this->loadTestTags();
        $this->assertTrue($tags['parent']->hasChildren());

        $child->delete();
        $this->assertFalse($tags['parent']->hasChildren());
    }

    public function testClonedTagWithAnotherIdDoesNotAnswerFromTheListingLookup(): void
    {
        $this->createTag('a');
        $tags = $this->loadTestTags();

        $clone = clone $tags['a'];
        $clone->setId(null);
        $clone->setName(self::PREFIX . 'clone');
        $clone->save();
        $this->createTag('clone_child', (int) $clone->getId());

        $this->assertTrue($clone->hasChildren());
    }

    public function testSavingATagInvalidatesEveryListingLookupOfTheProcess(): void
    {
        $a = $this->createTag('a');
        $this->createTag('b');

        // two listings of the same process, both with their own lookup, both already answered once
        $first = $this->loadTestTags();
        $second = $this->loadTestTags();
        $this->assertFalse($first['a']->hasChildren());
        $this->assertFalse($second['a']->hasChildren());

        // modified through a tag of the first listing, the second one must see it as well
        $this->createTag('a_child', (int) $a->getId());
        $this->assertTrue($first['a']->hasChildren());
        $this->assertTrue($second['a']->hasChildren());

        // deleted through a tag object of the second listing
        $fresh = $this->loadTestTags();
        $fresh['a_child']->delete();
        $this->assertFalse($first['a']->hasChildren());
        $this->assertFalse($second['a']->hasChildren());
        $this->assertFalse($fresh['a']->hasChildren());
    }

    public function testMovingATagToAnotherParentIsSeenByAllListings(): void
    {
        $a = $this->createTag('a');
        $b = $this->createTag('b');
        $child = $this->createTag('child', (int) $a->getId());

        $first = $this->loadTestTags();
        $second = $this->loadTestTags();
        $this->assertTrue($first['a']->hasChildren());
        $this->assertFalse($second['b']->hasChildren());

        $child->setParentId((int) $b->getId());
        $child->save();

        foreach ([$first, $second] as $tags) {
            $this->assertFalse($tags['a']->hasChildren());
            $this->assertTrue($tags['b']->hasChildren());
        }
    }

    public function testTagLoadedWithoutListingHasNoLookupAndAsksTheDatabase(): void
    {
        $parent = $this->createTag('parent');
        $leaf = $this->createTag('leaf');

        $byId = Tag::getById((int) $parent->getId());
        $this->assertNull($this->lookupOf($byId));
        $this->assertFalse($byId->hasChildren());

        // not cached by hasChildren(): a later change is seen at once
        $this->createTag('child', (int) $parent->getId());
        $this->assertTrue($byId->hasChildren());
        $this->assertFalse(Tag::getById((int) $leaf->getId())->hasChildren());
    }

    public function testListedTagsOfOneListingShareALookupAndListingsDoNot(): void
    {
        $this->createTag('a');
        $this->createTag('b');

        $first = $this->loadTestTags();
        $second = $this->loadTestTags();

        $this->assertNotNull($this->lookupOf($first['a']));
        $this->assertSame($this->lookupOf($first['a']), $this->lookupOf($first['b']));
        $this->assertNotSame($this->lookupOf($first['a']), $this->lookupOf($second['a']));
    }

    private function lookupOf(Tag $tag): ?object
    {
        $property = new ReflectionProperty(Tag::class, 'childrenLookup');

        return $property->getValue($tag);
    }

    public function testHasChildrenWhenAChildIsSavedBeforeTheFirstAnswer(): void
    {
        $this->createTag('a');
        $b = $this->createTag('b');

        $tags = $this->loadTestTags();
        $this->createTag('b_child', (int) $b->getId());

        $this->assertTrue($tags['b']->hasChildren());
        $this->assertFalse($tags['a']->hasChildren());
    }

    public function testHasChildrenOfSingleTagsAndPreloadedChildrenKeepWorking(): void
    {
        $parent = $this->createTag('parent');
        $this->createTag('child', (int) $parent->getId());
        $this->createTag('leaf');

        $this->assertTrue(Tag::getById((int) $parent->getId())->hasChildren());
        $this->assertFalse(Tag::getByPath('/' . self::PREFIX . 'leaf')->hasChildren());
        $this->assertFalse((new Tag())->hasChildren());

        $tags = $this->loadTestTags();
        $this->assertCount(1, $tags['parent']->getChildren());
        $this->assertTrue($tags['parent']->hasChildren());
    }

    public function testTagLoadedByPathHasNoLookupAndSeesLaterChanges(): void
    {
        $parent = $this->createTag('pathparent');
        $byPath = Tag::getByPath('/' . self::PREFIX . 'pathparent');

        $this->assertNull($this->lookupOf($byPath));
        $this->assertFalse($byPath->hasChildren());

        Db::get()->insert('tags', ['name' => self::PREFIX . 'pathchild', 'parentId' => $parent->getId(), 'idPath' => '/']);

        $this->assertTrue($byPath->hasChildren());
    }

    public function testRecursiveChildrenCostOneQueryPerLevel(): void
    {
        $root = $this->createTag('root');
        $level1 = [];
        for ($i = 1; $i <= 10; $i++) {
            $level1[$i] = $this->createTag("l1_$i", (int) $root->getId());
        }
        $this->createTag('l2', (int) $level1[3]->getId());

        $root = Tag::getById((int) $root->getId());
        $before = $this->selectCount();
        $children = $root->getChildren();
        $flags = array_map(static fn (Tag $tag): bool => $tag->hasChildren(), $children);
        $selects = $this->selectCount() - $before;

        $this->assertCount(10, $children);
        $this->assertSame(1, count(array_filter($flags)));
        $this->assertLessThanOrEqual(2, $selects, 'one query for the children, one for their hasChildren()');
    }

    public function testHasChildrenOverMoreTagsThanOneChunk(): void
    {
        $db = Db::get();
        $placeholders = [];
        $params = [];
        for ($i = 1; $i <= 1100; $i++) {
            $placeholders[] = "(?, 0, '/')";
            $params[] = sprintf('%sbulk%04d', self::PREFIX, $i);
        }
        $db->executeStatement('INSERT INTO tags (name, parentId, idPath) VALUES ' . implode(',', $placeholders), $params);

        $first = Tag::getByPath('/' . self::PREFIX . 'bulk0001');
        $last = Tag::getByPath('/' . self::PREFIX . 'bulk1100');
        $this->createTag('child_of_first', (int) $first->getId());
        $this->createTag('child_of_last', (int) $last->getId());

        $tags = $this->loadTestTags();
        $this->assertCount(1102, $tags);

        $before = $this->selectCount();
        $withChildren = [];
        foreach ($tags as $name => $tag) {
            if ($tag->hasChildren()) {
                $withChildren[] = $name;
            }
        }
        $selects = $this->selectCount() - $before;

        $this->assertSame(['bulk0001', 'bulk1100'], $withChildren);
        $this->assertLessThanOrEqual(2, $selects, '1102 tags are looked up in two chunks, not one query per tag');
    }

    public function testChildrenLookupIsNotPartOfTheStoredTag(): void
    {
        $this->createTag('parent');
        $tag = $this->loadTestTags()['parent'];
        $tag->hasChildren();

        $this->assertStringNotContainsString('childrenLookup', serialize($tag));

        $tag->save();
        $this->assertSame(
            (int) $tag->getId(),
            (int) Db::get()->fetchOne('SELECT id FROM tags WHERE id = ?', [$tag->getId()])
        );
    }
}
