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
    private LogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LogParser();
    }

    public function testMultilineFormatParseRoundTrip(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15T10:30:00+00:00');
        $message = "First line\nSecond line\nThird line";

        $formatted = $this->parser->formatEntry($createdAt, $message);
        $entries = $this->parser->parse($formatted);

        $this->assertCount(1, $entries);
        $this->assertSame($message, $entries[0]->getLogLine());
        $this->assertSame(
            $createdAt->format('c'),
            $entries[0]->getCreatedAt()->format('c')
        );
    }

    public function testLegacyNewlineDelimitedInput(): void
    {
        $legacy = "2024-01-15T10:30:00+00:00: First entry\n2024-01-15T10:31:00+00:00: Second entry";

        $entries = $this->parser->parse($legacy);

        $this->assertCount(2, $entries);
        $this->assertSame('First entry', $entries[0]->getLogLine());
        $this->assertSame('Second entry', $entries[1]->getLogLine());
    }

    public function testLegacyToNewAppendTransition(): void
    {
        // Simulate a log that starts with legacy newline-delimited entries
        // and then has new-format entries appended via formatEntry()
        $legacyPart = "2024-01-15T10:30:00+00:00: Legacy entry";

        $createdAt = new DateTimeImmutable('2024-01-15T10:31:00+00:00');
        $newPart = $this->parser->formatEntry($createdAt, "New format entry");

        $combined = $legacyPart . $newPart;
        $entries = $this->parser->parse($combined);

        $this->assertCount(2, $entries);
        $this->assertSame('Legacy entry', $entries[0]->getLogLine());
        $this->assertSame('New format entry', $entries[1]->getLogLine());
    }
}
