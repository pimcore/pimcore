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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Utils\ValueObjects;

use DateTimeImmutable;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidLogLineException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;
use Pimcore\Tests\Support\Test\TestCase;

class LogLineTest extends TestCase
{
    /**
     * The timestamp formats a historical log column may contain must all keep
     * resolving to the same instant, otherwise upgrading would silently drop
     * or shift the creation time of existing entries.
     *
     * @dataProvider supportedTimestampProvider
     */
    public function testAcceptsSupportedTimestampFormats(string $timestamp): void
    {
        $logLine = new LogLine($timestamp, 'a message');

        $this->assertSame(
            (new DateTimeImmutable('2024-01-15T10:30:00+00:00'))->getTimestamp(),
            $logLine->getCreatedAt()->getTimestamp()
        );
        $this->assertSame('a message', $logLine->getLogLine());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedTimestampProvider(): array
    {
        return [
            'ATOM (colon offset)' => ['2024-01-15T10:30:00+00:00'],
            'numeric offset without colon' => ['2024-01-15T10:30:00+0000'],
            'Zulu / UTC designator' => ['2024-01-15T10:30:00Z'],
        ];
    }

    public function testNonUtcOffsetIsPreserved(): void
    {
        $logLine = new LogLine('2024-01-15T10:30:00+05:30', 'msg');

        // 10:30 at +05:30 is 05:00 UTC.
        $this->assertSame(
            (new DateTimeImmutable('2024-01-15T05:00:00+00:00'))->getTimestamp(),
            $logLine->getCreatedAt()->getTimestamp()
        );
    }

    /**
     * @dataProvider invalidTimestampProvider
     */
    public function testThrowsOnInvalidTimestamp(string $timestamp): void
    {
        $this->expectException(InvalidLogLineException::class);

        new LogLine($timestamp, 'msg');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTimestampProvider(): array
    {
        return [
            'empty string' => [''],
            'not a date' => ['not-a-date'],
            'space instead of T separator' => ['2024-01-15 10:30:00+00:00'],
            'date only' => ['2024-01-15'],
            'trailing garbage' => ['2024-01-15T10:30:00+00:00 extra'],
        ];
    }

    /**
     * @dataProvider outOfRangeTimestampProvider
     */
    public function testRejectsOutOfRangeTimestamps(string $timestamp): void
    {
        // createFromFormat() silently overflows these (e.g. Feb 31 -> March 2) with only
        // a warning while still returning an object; the value object must reject them so
        // a corrupt timestamp can never be exposed as an accurate creation time.
        $this->expectException(InvalidLogLineException::class);

        new LogLine($timestamp, 'msg');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function outOfRangeTimestampProvider(): array
    {
        return [
            'day overflow (Feb 31)' => ['2024-02-31T10:30:00+00:00'],
            'month overflow (month 13)' => ['2024-13-01T10:30:00+00:00'],
            'hour overflow (25)' => ['2024-01-15T25:30:00+00:00'],
            'minute overflow (61)' => ['2024-01-15T10:61:00+00:00'],
            'second overflow (61)' => ['2024-01-15T10:30:61+00:00'],
        ];
    }

    public function testMessageIsStoredVerbatimIncludingNewlines(): void
    {
        // The value object no longer parses or reformats the message; the
        // LogParser owns segmentation, so multi-line payloads are kept as-is.
        $message = "line one\nline two\n  indented";

        $logLine = new LogLine('2024-01-15T10:30:00+00:00', $message);

        $this->assertSame($message, $logLine->getLogLine());
    }

    public function testAllowsEmptyMessage(): void
    {
        $logLine = new LogLine('2024-01-15T10:30:00+00:00', '');

        $this->assertSame('', $logLine->getLogLine());
    }
}
