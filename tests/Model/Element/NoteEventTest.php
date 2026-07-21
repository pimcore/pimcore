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
use Pimcore\Event\Model\ModelEvent;
use Pimcore\Event\NoteEvents;
use Pimcore\Model\Element\Note;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.element.note
 */
class NoteEventTest extends ModelTestCase
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

    private function createNote(): Note
    {
        $object = TestHelper::createEmptyObject();

        $note = new Note();
        $note->setElement($object);
        $note->setType('generic');
        $note->setDate(time());
        $note->save();

        return $note;
    }

    public function testPostAddEventFiredOnCreate(): void
    {
        $captured = null;
        $this->addListener(NoteEvents::POST_ADD, function (ModelEvent $event) use (&$captured): void {
            $captured = $event;
        });

        $note = $this->createNote();

        $this->assertInstanceOf(ModelEvent::class, $captured);
        $this->assertSame($note, $captured->getModel());
    }

    public function testPostDeleteEventFiredOnDelete(): void
    {
        $note = $this->createNote();

        $captured = null;
        $this->addListener(NoteEvents::POST_DELETE, function (ModelEvent $event) use (&$captured): void {
            $captured = $event;
        });

        $note->delete();

        $this->assertInstanceOf(ModelEvent::class, $captured);
        $this->assertSame($note, $captured->getModel());
    }

    public function testPostDeleteEventCarriesElementReference(): void
    {
        $note = $this->createNote();
        $expectedCid = $note->getCid();
        $expectedCtype = $note->getCtype();

        $capturedNote = null;
        $this->addListener(NoteEvents::POST_DELETE, function (ModelEvent $event) use (&$capturedNote): void {
            $model = $event->getModel();
            if ($model instanceof Note) {
                $capturedNote = $model;
            }
        });

        $note->delete();

        $this->assertInstanceOf(Note::class, $capturedNote);
        $this->assertSame($expectedCid, $capturedNote->getCid());
        $this->assertSame($expectedCtype, $capturedNote->getCtype());
    }

    public function testNoteIsRemovedFromDatabaseOnDelete(): void
    {
        $note = $this->createNote();
        $id = $note->getId();

        $this->assertNotNull($id);
        $this->assertInstanceOf(Note::class, Note::getById($id));

        $note->delete();

        $this->assertNull(Note::getById($id));
    }
}
