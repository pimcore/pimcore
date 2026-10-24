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

use Pimcore;
use Pimcore\Event\Model\TagEvent;
use Pimcore\Event\TagEvents;
use Pimcore\Model\Element\Tag;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.element.tag
 */
class TagEventTest extends ModelTestCase
{
    private array $registeredListeners = [];

    /**
     * @var string[] fired events in dispatch order, each "<event name> <tag name>:<element id>"
     */
    private array $fired = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fired = [];
        foreach ([
            TagEvents::PRE_ADD_TO_ELEMENT,
            TagEvents::POST_ADD_TO_ELEMENT,
            TagEvents::PRE_REMOVE_FROM_ELEMENT,
            TagEvents::POST_REMOVE_FROM_ELEMENT,
        ] as $eventName) {
            $listener = function (TagEvent $event) use ($eventName): void {
                $tagName = $event->getTag()->getName();
                $this->fired[] = $eventName . ' ' . $tagName . ':' . $event->getArgument('elementId');
            };
            Pimcore::getEventDispatcher()->addListener($eventName, $listener);
            $this->registeredListeners[] = [$eventName, $listener];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        parent::tearDown();
    }

    private function createTag(string $name): Tag
    {
        $tag = new Tag();
        $tag->setName($name . '_' . uniqid());
        $tag->save();

        return $tag;
    }

    /**
     * @return string[] "<tag name>:<element id>" of the fired events of one kind, without the event name prefix
     */
    private function firedOf(string $eventName): array
    {
        $result = [];
        foreach ($this->fired as $entry) {
            if (str_starts_with($entry, $eventName . ' ')) {
                $result[] = substr($entry, strlen($eventName) + 1);
            }
        }

        return $result;
    }

    private function key(Tag $tag, int $elementId): string
    {
        return $tag->getName() . ':' . $elementId;
    }

    public function testSetTagsForElementFiresEventsForTheDifference(): void
    {
        $object = TestHelper::createEmptyObject();
        $id = $object->getId();
        $kept = $this->createTag('kept');
        $removed = $this->createTag('removed');
        $added = $this->createTag('added');
        Tag::setTagsForElement('object', $id, [$kept, $removed]);
        $this->fired = [];

        Tag::setTagsForElement('object', $id, [$kept, $added]);

        $this->assertSame([$this->key($added, $id)], $this->firedOf(TagEvents::PRE_ADD_TO_ELEMENT));
        $this->assertSame([$this->key($added, $id)], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
        $this->assertSame([$this->key($removed, $id)], $this->firedOf(TagEvents::PRE_REMOVE_FROM_ELEMENT));
        $this->assertSame([$this->key($removed, $id)], $this->firedOf(TagEvents::POST_REMOVE_FROM_ELEMENT));

        $names = array_map(static fn (Tag $tag) => $tag->getName(), Tag::getTagsForElement('object', $id));
        $this->assertEqualsCanonicalizing([$kept->getName(), $added->getName()], $names);
    }

    public function testSetTagsForElementWithEmptyArrayFiresRemoveEvents(): void
    {
        $object = TestHelper::createEmptyObject();
        $id = $object->getId();
        $first = $this->createTag('first');
        $second = $this->createTag('second');
        Tag::setTagsForElement('object', $id, [$first, $second]);
        $this->fired = [];

        Tag::setTagsForElement('object', $id, []);

        $this->assertEqualsCanonicalizing(
            [$this->key($first, $id), $this->key($second, $id)],
            $this->firedOf(TagEvents::POST_REMOVE_FROM_ELEMENT)
        );
        $this->assertSame([], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
        $this->assertSame([], Tag::getTagsForElement('object', $id));
    }

    public function testSetTagsForElementWithUnchangedTagsFiresNothing(): void
    {
        $object = TestHelper::createEmptyObject();
        $tag = $this->createTag('same');
        Tag::setTagsForElement('object', $object->getId(), [$tag]);
        $this->fired = [];

        Tag::setTagsForElement('object', $object->getId(), [$tag]);

        $this->assertSame([], $this->fired);
    }

    public function testBatchAssignFiresEventsPerElementAndTag(): void
    {
        $first = TestHelper::createEmptyObject()->getId();
        $second = TestHelper::createEmptyObject()->getId();
        $tag = $this->createTag('batch');
        $alreadyAssigned = $this->createTag('assigned');
        Tag::addTagToElement('object', $first, $alreadyAssigned);
        $this->fired = [];

        Tag::batchAssignTagsToElement('object', [$first, $second], [$tag->getId(), $alreadyAssigned->getId()]);

        $this->assertEqualsCanonicalizing(
            [$this->key($tag, $first), $this->key($tag, $second), $this->key($alreadyAssigned, $second)],
            $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT)
        );
        $this->assertEqualsCanonicalizing(
            $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT),
            $this->firedOf(TagEvents::PRE_ADD_TO_ELEMENT)
        );
        $this->assertSame([], $this->firedOf(TagEvents::POST_REMOVE_FROM_ELEMENT));
    }

    public function testBatchAssignWithReplaceFiresRemoveEventsForDroppedTags(): void
    {
        $first = TestHelper::createEmptyObject()->getId();
        $second = TestHelper::createEmptyObject()->getId();
        $old = $this->createTag('old');
        $new = $this->createTag('new');
        Tag::addTagToElement('object', $first, $old);
        Tag::addTagToElement('object', $second, $new);
        $this->fired = [];

        Tag::batchAssignTagsToElement('object', [$first, $second], [$new->getId()], true);

        $this->assertSame([$this->key($old, $first)], $this->firedOf(TagEvents::PRE_REMOVE_FROM_ELEMENT));
        $this->assertSame([$this->key($old, $first)], $this->firedOf(TagEvents::POST_REMOVE_FROM_ELEMENT));
        $this->assertSame([$this->key($new, $first)], $this->firedOf(TagEvents::PRE_ADD_TO_ELEMENT));
        $this->assertSame([$this->key($new, $first)], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
        $this->assertSame([$new->getName()], array_map(
            static fn (Tag $tag) => $tag->getName(),
            Tag::getTagsForElement('object', $first)
        ));
    }

    public function testSetTagsForElementIgnoresDuplicateTagsInTheInput(): void
    {
        $id = TestHelper::createEmptyObject()->getId();
        $tag = $this->createTag('duplicate');

        Tag::setTagsForElement('object', $id, [$tag, $tag]);

        $this->assertSame([$this->key($tag, $id)], $this->firedOf(TagEvents::PRE_ADD_TO_ELEMENT));
        $this->assertSame([$this->key($tag, $id)], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
        $this->assertCount(1, Tag::getTagsForElement('object', $id));
    }

    public function testBatchAssignIgnoresDuplicateTagAndElementIds(): void
    {
        $id = TestHelper::createEmptyObject()->getId();
        $tag = $this->createTag('batchduplicate');

        Tag::batchAssignTagsToElement('object', [$id, $id], [$tag->getId(), $tag->getId()]);

        $this->assertSame([$this->key($tag, $id)], $this->firedOf(TagEvents::PRE_ADD_TO_ELEMENT));
        $this->assertSame([$this->key($tag, $id)], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
    }

    public function testPreEventsFireBeforeAndPostEventsAfterTheWrite(): void
    {
        $id = TestHelper::createEmptyObject()->getId();
        $tag = $this->createTag('order');
        $seenInPre = null;
        $seenInPost = null;
        $pre = function () use (&$seenInPre, $id): void {
            $seenInPre = count(Tag::getTagsForElement('object', $id));
        };
        $post = function () use (&$seenInPost, $id): void {
            $seenInPost = count(Tag::getTagsForElement('object', $id));
        };
        Pimcore::getEventDispatcher()->addListener(TagEvents::PRE_ADD_TO_ELEMENT, $pre);
        Pimcore::getEventDispatcher()->addListener(TagEvents::POST_ADD_TO_ELEMENT, $post);
        $this->registeredListeners[] = [TagEvents::PRE_ADD_TO_ELEMENT, $pre];
        $this->registeredListeners[] = [TagEvents::POST_ADD_TO_ELEMENT, $post];

        Tag::setTagsForElement('object', $id, [$tag]);

        $this->assertSame(0, $seenInPre);
        $this->assertSame(1, $seenInPost);
    }

    public function testFailingBatchAssignRollsBackAndFiresNoPostEvents(): void
    {
        $element = TestHelper::createEmptyObject()->getId();
        $old = $this->createTag('old');
        $new = $this->createTag('new');
        Tag::addTagToElement('object', $element, $old);
        $this->fired = [];

        try {
            // 'x' is no valid element id: the first insert succeeds, the second one throws
            Tag::batchAssignTagsToElement('object', [$element, 'x'], [$new->getId()], true);
            $this->fail('The batch write should have failed');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e);
        }

        $this->assertSame([], $this->firedOf(TagEvents::POST_ADD_TO_ELEMENT));
        $this->assertSame([], $this->firedOf(TagEvents::POST_REMOVE_FROM_ELEMENT));
        $assigned = array_map(fn (Tag $tag) => $tag->getId(), Tag::getTagsForElement('object', $element));
        $this->assertSame([$old->getId()], $assigned);
    }

    public function testBatchEventIsStillFired(): void
    {
        $id = TestHelper::createEmptyObject()->getId();
        $tag = $this->createTag('batchevent');
        $calls = 0;
        $listener = function () use (&$calls): void {
            $calls++;
        };
        Pimcore::getEventDispatcher()->addListener(TagEvents::POST_BATCH_ASSIGN_TAGS_TO_ELEMENT, $listener);
        $this->registeredListeners[] = [TagEvents::POST_BATCH_ASSIGN_TAGS_TO_ELEMENT, $listener];

        Tag::batchAssignTagsToElement('object', [$id], [$tag->getId()]);

        $this->assertSame(1, $calls);
    }
}
