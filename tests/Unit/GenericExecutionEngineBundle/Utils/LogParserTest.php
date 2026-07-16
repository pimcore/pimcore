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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Utils;

use DateTimeImmutable;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\LogParser;
use Pimcore\Tests\Support\Test\TestCase;

class LogParserTest extends TestCase
{
    /**
     * ASCII record separator (0x1E) that delimits entries in the stored column.
     */
    private const RS = "\x1e";

    private const FIXED_TIMESTAMP = '2024-01-15T10:30:00+00:00';

    private LogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LogParser();
    }

    // -----------------------------------------------------------------
    // Concrete stored format
    //
    // These assert the exact bytes produced by formatEntry() so the suite
    // is not only proving that parse(format($x)) === $x (which a pair of
    // compensating bugs could satisfy) but that the on-disk representation
    // is actually the record-separator format we claim to write.
    // -----------------------------------------------------------------

    public function testFormatEntryPrefixesRecordSeparatorAndTimestamp(): void
    {
        $formatted = $this->parser->formatEntry(
            new DateTimeImmutable(self::FIXED_TIMESTAMP),
            'hello world'
        );

        $this->assertSame(self::RS . self::FIXED_TIMESTAMP . ': hello world', $formatted);
    }

    public function testFormatEntryEscapesPayloadSoTheDelimiterStaysUnambiguous(): void
    {
        // A message carrying both a record separator and a percent sign must
        // be percent-encoded (RS -> %1E, % -> %25) so the only real delimiter
        // in the result is the single leading one.
        $formatted = $this->parser->formatEntry(
            new DateTimeImmutable(self::FIXED_TIMESTAMP),
            'a' . self::RS . 'b%c'
        );

        $this->assertSame(self::RS . self::FIXED_TIMESTAMP . ': a%1Eb%25c', $formatted);
        $this->assertSame(1, substr_count($formatted, self::RS));
    }

    public function testFormatEntryTrimsSurroundingWhitespace(): void
    {
        $formatted = $this->parser->formatEntry(
            new DateTimeImmutable(self::FIXED_TIMESTAMP),
            "  padded message  \n"
        );

        $this->assertSame(self::RS . self::FIXED_TIMESTAMP . ': padded message', $formatted);
    }

    // -----------------------------------------------------------------
    // Round-trip payload preservation (record-separator format)
    // -----------------------------------------------------------------

    /**
     * @dataProvider messagePreservationProvider
     */
    public function testMessageSurvivesFormatParseRoundTrip(string $message): void
    {
        $createdAt = new DateTimeImmutable(self::FIXED_TIMESTAMP);

        $entries = $this->parser->parse($this->parser->formatEntry($createdAt, $message));

        $this->assertCount(1, $entries);
        $this->assertSame($message, $entries[0]->getLogLine());
        $this->assertSame(
            $createdAt->getTimestamp(),
            $entries[0]->getCreatedAt()->getTimestamp()
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function messagePreservationProvider(): array
    {
        return [
            'plain single line' => ['Simple message'],
            'multi-line message' => ["First line\nSecond line\nThird line"],
            'raw record separator in payload' => ['before' . self::RS . 'after'],
            'percent sign' => ['Progress: 50% done'],
            // The follow-up "reversible percent-encoding" fix must keep the
            // literal escape tokens intact instead of decoding them back.
            'literal %25 token' => ['50%25 off'],
            'literal %1E token' => ['token %1E stays'],
            // The headline reason for the new format: a message that itself
            // begins with a timestamp + ": " used to be split into two entries.
            'payload that looks like a timestamped entry' => [
                '2024-01-15T10:31:00+00:00: this is a message, not a new entry',
            ],
            'multiple colon-space sequences' => ['key: value: another'],
            'empty message' => [''],
            'multibyte payload' => ['Ünïcödé — 你好 — 🎉'],
        ];
    }

    // -----------------------------------------------------------------
    // Malformed / defensive input
    // -----------------------------------------------------------------

    /**
     * @dataProvider emptyLogProvider
     */
    public function testParseReturnsEmptyArrayForEmptyLog(?string $log): void
    {
        $this->assertSame([], $this->parser->parse($log));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function emptyLogProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    public function testParseSkipsCorruptSegmentButKeepsSurroundingEntries(): void
    {
        // A new-format segment that does not start with a valid timestamp is
        // dropped; entries before AND after it must still be returned, so a
        // single corrupt segment can never swallow the rest of the log.
        $createdAt = new DateTimeImmutable(self::FIXED_TIMESTAMP);
        $log = $this->parser->formatEntry($createdAt, 'first')
            . self::RS . 'corrupt segment without timestamp'
            . $this->parser->formatEntry($createdAt, 'second');

        $entries = $this->parser->parse($log);

        $this->assertCount(2, $entries);
        $this->assertSame('first', $entries[0]->getLogLine());
        $this->assertSame('second', $entries[1]->getLogLine());
    }

    public function testParseSkipsSegmentWithOutOfRangeTimestampButKeepsSurroundingEntries(): void
    {
        // A segment whose timestamp is shaped like a real one but is out of range
        // (Feb 31) must be dropped without aborting the surrounding, valid entries.
        $createdAt = new DateTimeImmutable(self::FIXED_TIMESTAMP);
        $log = $this->parser->formatEntry($createdAt, 'first')
            . self::RS . '2024-02-31T10:30:00+00:00: overflowing entry'
            . $this->parser->formatEntry($createdAt, 'second');

        $entries = $this->parser->parse($log);

        $this->assertCount(2, $entries);
        $this->assertSame('first', $entries[0]->getLogLine());
        $this->assertSame('second', $entries[1]->getLogLine());
    }

    // -----------------------------------------------------------------
    // Backward compatibility: legacy newline-delimited logs
    //
    // Logs written before this change used "\n" as the entry delimiter and
    // stored messages without any percent-encoding. They must keep parsing.
    // -----------------------------------------------------------------

    public function testLegacyNewlineDelimitedInput(): void
    {
        $legacy = self::FIXED_TIMESTAMP . ": First entry\n"
            . '2024-01-15T10:31:00+00:00: Second entry';

        $entries = $this->parser->parse($legacy);

        $this->assertCount(2, $entries);
        $this->assertSame('First entry', $entries[0]->getLogLine());
        $this->assertSame('Second entry', $entries[1]->getLogLine());
        $this->assertSame(
            (new DateTimeImmutable(self::FIXED_TIMESTAMP))->getTimestamp(),
            $entries[0]->getCreatedAt()->getTimestamp()
        );
    }

    public function testLegacyMultiLineMessageIsKeptAsSingleEntry(): void
    {
        // Continuation lines (not starting with a timestamp) belong to the
        // preceding entry's message, newline included.
        $legacy = self::FIXED_TIMESTAMP . ": summary line\n"
            . "  stack frame #0\n"
            . '  stack frame #1';

        $entries = $this->parser->parse($legacy);

        $this->assertCount(1, $entries);
        $this->assertSame(
            "summary line\n  stack frame #0\n  stack frame #1",
            $entries[0]->getLogLine()
        );
    }

    public function testLegacyCarriageReturnsAreStripped(): void
    {
        // Windows-style line endings in an old log must not leak "\r" into
        // the reconstructed multi-line message.
        $legacy = self::FIXED_TIMESTAMP . ": first\r\nsecond";

        $entries = $this->parser->parse($legacy);

        $this->assertCount(1, $entries);
        $this->assertSame("first\nsecond", $entries[0]->getLogLine());
    }

    public function testLegacyMessagesAreNotPercentDecoded(): void
    {
        // Legacy messages were stored verbatim (no escaping). The percent
        // decoding only applies to the new format, so a legacy "%25" must be
        // returned literally, never turned into "%".
        $legacy = self::FIXED_TIMESTAMP . ': 50%25 and %1E stay literal';

        $entries = $this->parser->parse($legacy);

        $this->assertCount(1, $entries);
        $this->assertSame('50%25 and %1E stay literal', $entries[0]->getLogLine());
    }

    /**
     * @dataProvider legacyTimestampVariantProvider
     */
    public function testLegacyTimestampFormatVariantsAreParsed(string $timestamp): void
    {
        $entries = $this->parser->parse($timestamp . ': message');

        $this->assertCount(1, $entries);
        $this->assertSame('message', $entries[0]->getLogLine());
        $this->assertSame(
            (new DateTimeImmutable(self::FIXED_TIMESTAMP))->getTimestamp(),
            $entries[0]->getCreatedAt()->getTimestamp()
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function legacyTimestampVariantProvider(): array
    {
        return [
            'ATOM (colon offset)' => ['2024-01-15T10:30:00+00:00'],
            'numeric offset without colon' => ['2024-01-15T10:30:00+0000'],
            'Zulu / UTC designator' => ['2024-01-15T10:30:00Z'],
        ];
    }

    public function testLegacyOutOfRangeTimestampEntryIsSkipped(): void
    {
        // The same guarantee for the legacy newline format: a malformed entry in the
        // middle is dropped, and the valid entries before and after it are kept.
        $legacy = self::FIXED_TIMESTAMP . ": good one\n"
            . "2024-02-31T10:30:00+00:00: overflowing entry\n"
            . '2024-01-15T10:32:00+00:00: another good one';

        $entries = $this->parser->parse($legacy);

        $this->assertCount(2, $entries);
        $this->assertSame('good one', $entries[0]->getLogLine());
        $this->assertSame('another good one', $entries[1]->getLogLine());
    }

    public function testLegacyToNewAppendTransition(): void
    {
        // A log that starts life in the legacy newline format and then has a
        // new record-separator entry appended (the real upgrade scenario)
        // must yield both entries.
        $legacyPart = self::FIXED_TIMESTAMP . ': Legacy entry';
        $newPart = $this->parser->formatEntry(
            new DateTimeImmutable('2024-01-15T10:31:00+00:00'),
            'New format entry'
        );

        $entries = $this->parser->parse($legacyPart . $newPart);

        $this->assertCount(2, $entries);
        $this->assertSame('Legacy entry', $entries[0]->getLogLine());
        $this->assertSame('New format entry', $entries[1]->getLogLine());
    }

    public function testLegacyMultiLineSplitsOnTimestampLikeContinuationIsAcceptedLimitation(): void
    {
        // Documented best-effort limitation: in the OLD format there was no
        // way to tell a genuine continuation line apart from a line that
        // happens to look like a new timestamped entry, so this legacy blob
        // is (knowingly) split into two entries. This is exactly the ambiguity
        // the record-separator format removes going forward — compare with the
        // "payload that looks like a timestamped entry" round-trip case, which
        // stays a single entry.
        $legacy = self::FIXED_TIMESTAMP . ": intended single message\n"
            . '2024-01-15T10:31:00+00:00: looks like a new entry';

        $entries = $this->parser->parse($legacy);

        $this->assertCount(2, $entries);
    }
}
