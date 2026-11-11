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

namespace Pimcore\Tests\Unit\Notification\Service;

use InvalidArgumentException;
use Pimcore\Model\Notification\Service\NotificationServiceFilterParser;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NotificationServiceFilterParserTest extends TestCase
{
    private function buildRequest(array $filter): Request
    {
        return Request::create('/admin/notifications/find', 'POST', [
            'filter' => json_encode($filter),
        ]);
    }

    public function testWhitelistedStringPropertyIsAccepted(): void
    {
        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'string', 'property' => 'title', 'operator' => 'like', 'value' => 'test'],
        ]));

        $result = $parser->parse();

        $this->assertArrayHasKey('title_like', $result);
        $this->assertSame('title LIKE :title_like', $result['title_like']['condition']);
    }

    public function testWhitelistedDateOperatorIsAccepted(): void
    {
        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'date', 'property' => 'timestamp', 'operator' => 'gt', 'value' => '2026-01-01'],
        ]));

        $result = $parser->parse();

        $this->assertArrayHasKey('creationDate_gt', $result);
        $this->assertSame('creationDate > :creationDate_gt', $result['creationDate_gt']['condition']);
    }

    /**
     * creationDate is stored in UTC; a filter entered as a local-timezone day has to be
     * converted, otherwise the day window is shifted by the zone offset.
     */
    public function testDateFilterValueIsConvertedToUtc(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $parser = new NotificationServiceFilterParser($this->buildRequest([
                ['type' => 'date', 'property' => 'timestamp', 'operator' => 'gt', 'value' => '2026-07-01'],
            ]));

            $result = $parser->parse();

            // 2026-07-01 00:00 Europe/Berlin (CEST, UTC+2) is 2026-06-30 22:00 UTC
            $this->assertSame(
                '2026-06-30 22:00:00',
                $result['creationDate_gt']['conditionVariables']['creationDate_gt']
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    /**
     * The day window must cover exactly the local calendar day even across DST
     * transitions, where a local day is 23 or 25 hours long. Boundaries are derived in
     * the application timezone and only converted to UTC when binding - converting
     * first and adding 24h would bleed an hour into the neighbouring day.
     */
    public function testDateFilterKeepsLocalDayLengthAcrossDstTransition(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            // 2026-03-29 is a 23-hour day in Europe/Berlin (CET +01:00 -> CEST +02:00)
            $parser = new NotificationServiceFilterParser($this->buildRequest([
                ['type' => 'date', 'property' => 'timestamp', 'operator' => 'eq', 'value' => '2026-03-29'],
            ]));

            $result = $parser->parse();

            $this->assertSame(
                [
                    'creationDate_eq_start' => '2026-03-28 23:00:00',
                    'creationDate_eq_end' => '2026-03-29 21:59:59',
                ],
                $result['creationDate_eq']['conditionVariables']
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testUnknownStringPropertyIsRejected(): void
    {
        // Not a whitelisted property: previously fell through to being used
        // verbatim as a raw SQL column name with no escaping.
        $payload = '1 UNION SELECT id,username,password,email,5 FROM users-- -';

        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'string', 'property' => $payload, 'operator' => 'like', 'value' => ''],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $parser->parse();
    }

    public function testArrayValuedPropertyIsRejectedWithoutTypeError(): void
    {
        // A JSON filter can supply `property` as an array; must be rejected
        // cleanly with InvalidArgumentException, not a raw TypeError from
        // using an array as an array offset.
        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'string', 'property' => ['title'], 'operator' => 'like', 'value' => ''],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $parser->parse();
    }

    public function testUnknownDatePropertyIsRejected(): void
    {
        $payload = '(SELECT 1 FROM (SELECT SLEEP(5))t)-- -';

        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'date', 'property' => $payload, 'operator' => 'gt', 'value' => '2026-01-01'],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $parser->parse();
    }
}
