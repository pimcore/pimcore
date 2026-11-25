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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\MessengerClearRuntimeCacheListener;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Tests\Support\Test\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

class MessengerClearRuntimeCacheListenerTest extends TestCase
{
    private const KEY_ID = 7;

    private const TITLE_BEFORE_THE_EDIT = 'Before the edit';

    private const TITLE_AFTER_THE_EDIT = 'After the edit';

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearClassificationStoreCaches();
    }

    protected function tearDown(): void
    {
        $this->clearClassificationStoreCaches();

        parent::tearDown();
    }

    /**
     * A worker handles one message after another in one process, so a field definition edited
     * between two of them must not be answered from the memo
     * {@see Service::getFieldDefinitionFromKeyConfig()} keeps - which the runtime cache does not
     * hold, as it is a static of the Service's own.
     */
    public function testTheDefinitionMemoDoesNotSurviveTheMessageBoundary(): void
    {
        $keyConfig = $this->keyConfig(self::TITLE_BEFORE_THE_EDIT);

        $this->assertSame(
            self::TITLE_BEFORE_THE_EDIT,
            Service::getFieldDefinitionFromKeyConfig($keyConfig)->getTitle()
        );

        $keyConfig->setDefinition($this->definition(self::TITLE_AFTER_THE_EDIT));

        $this->assertSame(
            self::TITLE_BEFORE_THE_EDIT,
            Service::getFieldDefinitionFromKeyConfig($keyConfig)->getTitle(),
            'within one message the memo is what makes the lookup free'
        );

        $this->handleMessage();

        $this->assertSame(
            self::TITLE_AFTER_THE_EDIT,
            Service::getFieldDefinitionFromKeyConfig($keyConfig)->getTitle()
        );
    }

    /**
     * The memo is built from the key config {@see DefinitionCache} hands out, so dropping the memo
     * alone would have it rebuilt from a key config that is just as stale.
     */
    public function testTheKeyConfigCacheDoesNotSurviveTheMessageBoundary(): void
    {
        $keyConfig = $this->keyConfig(self::TITLE_BEFORE_THE_EDIT);
        DefinitionCache::put($keyConfig);

        $this->assertSame($keyConfig, DefinitionCache::get(self::KEY_ID));

        $this->handleMessage();

        $this->assertArrayNotHasKey('key' . self::KEY_ID, DefinitionCache::$cache);
    }

    private function handleMessage(): void
    {
        $listener = new MessengerClearRuntimeCacheListener();
        $listener->handle(new WorkerMessageReceivedEvent(new Envelope(new stdClass()), 'pimcore_core'));
    }

    private function keyConfig(string $title): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        $keyConfig->setType('input');
        $keyConfig->setDefinition($this->definition($title));

        return $keyConfig;
    }

    private function definition(string $title): string
    {
        return json_encode(['name' => 'csKey', 'title' => $title]);
    }

    private function clearClassificationStoreCaches(): void
    {
        Service::clearDefinitionsCache();
        DefinitionCache::clear(null);
    }
}
