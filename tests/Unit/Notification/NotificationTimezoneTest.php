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

namespace Pimcore\Tests\Unit\Notification;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * `notifications.creationDate` / `modificationDate` are stored in UTC - see migration
 * Version20230321133700, which converted the pre-existing rows - and every reader
 * (NotificationService::format(), Studio's NotificationHydrator) parses them as UTC.
 * Dao::save() has to honour that convention, otherwise the displayed time is shifted
 * by the configured `general.timezone` offset.
 */
class NotificationTimezoneTest extends TestCase
{
    private const TEST_TIMEZONE = 'Europe/Berlin';

    private string $originalTimezone;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set(self::TEST_TIMEZONE);
    }

    public function _after(): void
    {
        date_default_timezone_set($this->originalTimezone);

        $user = User::getByName('notification-timezone-user');
        if ($user instanceof User) {
            Pimcore::getContainer()->get(NotificationService::class)->deleteAll($user->getId());
            $user->delete();
        }
    }

    public function testSaveStoresDatesInUtc(): void
    {
        $before = time();
        $notification = $this->createNotification();
        $after = time();

        $dates = [
            'creationDate' => $notification->getCreationDate(),
            'modificationDate' => $notification->getModificationDate(),
        ];

        foreach ($dates as $field => $stored) {
            $this->assertNotNull($stored, $field . ' was not set');

            $utc = new DateTimeImmutable($stored, new DateTimeZone('UTC'));
            $this->assertGreaterThanOrEqual($before, $utc->getTimestamp(), $field . ' is not UTC');
            $this->assertLessThanOrEqual($after, $utc->getTimestamp(), $field . ' is not UTC');
        }
    }

    /**
     * The value also has to survive the database round trip as UTC. These are `TIMESTAMP`
     * columns, which MySQL renders in its own session timezone on read, so the stored
     * string is only usable if the writer and the readers agree on the convention.
     */
    public function testCreationDateSurvivesTheDatabaseRoundTripAsUtc(): void
    {
        $before = time();
        $notification = $this->createNotification();
        $after = time();

        // The expression used by both NotificationService::format() and Studio's
        // NotificationHydrator.
        $timestamp = (new Carbon($this->reRead($notification)->getCreationDate(), 'UTC'))
            ->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    public function testFormattedTimestampMatchesTheRealEventTime(): void
    {
        $before = time();
        $notification = $this->createNotification();
        $after = time();

        $formatted = Pimcore::getContainer()
            ->get(NotificationService::class)
            ->format($this->reRead($notification));

        $this->assertGreaterThanOrEqual($before, $formatted['timestamp']);
        $this->assertLessThanOrEqual($after, $formatted['timestamp']);
    }

    /**
     * The Classic UI polls findLastUnread() with a unix epoch to show live popups.
     * The bound derived from that epoch has to be rendered in UTC like the stored
     * values - a local-time bound sits ahead of every fresh row by the zone offset,
     * silently suppressing the popups.
     */
    public function testFindLastUnreadFindsAFreshNotification(): void
    {
        $notification = $this->createNotification();
        $recipientId = (int) $notification->getRecipient()->getId();

        $service = Pimcore::getContainer()->get(NotificationService::class);

        $result = $service->findLastUnread($recipientId, time() - 60);
        $this->assertSame(1, $result['total'], 'fresh notification not found by the unread poller');

        // Negative control: a bound in the future must not match.
        $result = $service->findLastUnread($recipientId, time() + 3600);
        $this->assertSame(0, $result['total']);
    }

    public function testStoredDateIsNotLocalWallClockTime(): void
    {
        $notification = $this->createNotification();

        // Guard against the regression: writing local time made readers - which parse the
        // value as UTC - report a timestamp shifted by the zone offset (+2h during DST).
        $localOffset = (new DateTimeZone(self::TEST_TIMEZONE))
            ->getOffset(new DateTimeImmutable('@' . time()));
        $this->assertNotSame(0, $localOffset, 'test timezone must have a non-zero UTC offset');

        $stored = new DateTimeImmutable($notification->getCreationDate(), new DateTimeZone('UTC'));
        $this->assertLessThan(
            abs($localOffset),
            abs($stored->getTimestamp() - time()),
            'creationDate was stored as local wall-clock time instead of UTC'
        );
    }

    /**
     * Re-read from the database, bypassing the runtime cache.
     */
    private function reRead(Notification $notification): Notification
    {
        RuntimeCache::clear();

        $reRead = Notification::getById((int) $notification->getId());
        $this->assertInstanceOf(Notification::class, $reRead);

        return $reRead;
    }

    private function createNotification(): Notification
    {
        $user = User::getByName('notification-timezone-user');

        if (!$user instanceof User) {
            $user = new User();
            $user->setName('notification-timezone-user');
            $user->save();
        }

        $notification = new Notification();
        $notification->setRecipient($user);
        $notification->setTitle('Test title');
        $notification->setMessage('Test message');
        $notification->save();

        return $notification;
    }
}
