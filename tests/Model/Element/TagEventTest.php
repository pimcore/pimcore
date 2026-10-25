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

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        parent::tearDown();
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
    }

    private function createTag(string $name): Tag
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->save();

        return $tag;
    }

    public function testSetTagsForElementDispatchesPostBatchAssignEvent(): void
    {
        $object = TestHelper::createEmptyObject();
        $tagA = $this->createTag('pme_t2714_a');
        $tagB = $this->createTag('pme_t2714_b');

        /** @var TagEvent[] $captured */
        $captured = [];
        $this->addListener(TagEvents::POST_BATCH_ASSIGN_TAGS_TO_ELEMENT, function (TagEvent $event) use (&$captured): void {
            $captured[] = $event;
        });

        Tag::setTagsForElement('object', $object->getId(), [$tagA, $tagB]);

        $this->assertCount(1, $captured);
        $this->assertSame('object', $captured[0]->getArgument('elementType'));
        $this->assertSame([$object->getId()], $captured[0]->getArgument('elementIds'));
        $this->assertSame([$tagA->getId(), $tagB->getId()], $captured[0]->getArgument('tagIds'));
        $this->assertCount(2, Tag::getTagsForElement('object', $object->getId()));
    }

    public function testSetTagsForElementWithEmptyArrayDispatchesEvent(): void
    {
        $object = TestHelper::createEmptyObject();
        $tag = $this->createTag('pme_t2714_c');
        Tag::setTagsForElement('object', $object->getId(), [$tag]);

        $captured = [];
        $this->addListener(TagEvents::POST_BATCH_ASSIGN_TAGS_TO_ELEMENT, function (TagEvent $event) use (&$captured): void {
            $captured[] = $event;
        });

        Tag::setTagsForElement('object', $object->getId(), []);

        $this->assertCount(1, $captured);
        $this->assertSame([$object->getId()], $captured[0]->getArgument('elementIds'));
        $this->assertSame([], $captured[0]->getArgument('tagIds'));
        $this->assertSame([], Tag::getTagsForElement('object', $object->getId()));
    }
}
