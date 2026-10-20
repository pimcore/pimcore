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

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Model\Document\Editable\Scheduledblock;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group model.document.document
 */
class ScheduledblockTest extends ModelTestCase
{
    private FullPageCacheListener $fullPageCache;

    private bool $fullPageCacheWasEnabled;

    private ?int $fullPageCacheLifetime;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fullPageCache = Pimcore::getContainer()->get(FullPageCacheListener::class);
        $this->fullPageCacheWasEnabled = $this->fullPageCache->isEnabled();
        $this->fullPageCacheLifetime = $this->fullPageCache->getLifetime();

        // the output timestamp is stored on the main request
        $this->requestStack = Pimcore::getContainer()->get('request_stack');
        $this->requestStack->push(new Request());
    }

    protected function tearDown(): void
    {
        $this->requestStack->pop();
        $this->fullPageCacheWasEnabled ? $this->fullPageCache->enable() : $this->fullPageCache->disable();
        $this->fullPageCache->setLifetime($this->fullPageCacheLifetime);

        parent::tearDown();
    }

    /**
     * @dataProvider cacheStates
     */
    public function testLoopRendersCurrentEntryWhenNoEntryLiesInTheFuture(bool $fullPageCacheEnabled): void
    {
        $fullPageCacheEnabled ? $this->fullPageCache->enable() : $this->fullPageCache->disable();
        $this->fullPageCache->setLifetime(null);

        $editable = $this->createScheduledblock([time() - 7200, time() - 3600]);

        $entries = $this->iterate($editable);

        $this->assertSame(1, $entries);
        $this->assertNull($this->fullPageCache->getLifetime(), 'no next entry, the cache lifetime stays unchanged');
    }

    public static function cacheStates(): array
    {
        return [
            'full page cache disabled' => [false],
            'full page cache enabled' => [true],
        ];
    }

    public function testCacheLifetimeIsShortenedToTheNextEntry(): void
    {
        $this->fullPageCache->enable();
        $this->fullPageCache->setLifetime(null);

        $editable = $this->createScheduledblock([time() - 3600, time() + 7200]);

        $this->assertSame(1, $this->iterate($editable));

        $lifetime = $this->fullPageCache->getLifetime();
        $this->assertNotNull($lifetime);
        $this->assertEqualsWithDelta(7200, $lifetime, 5);
    }

    private function createScheduledblock(array $dates): Scheduledblock
    {
        $editable = new Scheduledblock();
        $editable->setName('scheduledblock');
        $editable->setConfig(['limit' => 5]);
        $editable->setDataFromEditmode(array_map(
            fn ($date, $key) => ['key' => $key, 'date' => $date],
            $dates,
            array_keys($dates)
        ));

        return $editable;
    }

    private function iterate(Scheduledblock $editable): int
    {
        $entries = 0;
        ob_start();

        try {
            while ($editable->loop()) {
                $entries++;
            }
        } finally {
            ob_end_clean();
        }

        return $entries;
    }
}
