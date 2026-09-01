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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\EventSanitizer;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

class EventSanitizerTest extends TestCase
{
    private EventSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new EventSanitizer($this->createMock(LoggerInterface::class));
    }

    /**
     * @dataProvider validEventNames
     */
    public function testAcceptsTaxonomyConformantNames(string $event): void
    {
        $this->assertTrue($this->sanitizer->isValidEventName($event));
    }

    public static function validEventNames(): array
    {
        return [
            ['object.opened'],
            ['object.created'],
            ['asset.uploaded'],
            ['asset.version_created'],
            ['importer.run_started'],
            ['instance.snapshot'],
            ['error.occurred'],
        ];
    }

    /**
     * @dataProvider invalidEventNames
     */
    public function testRejectsNonConformantNames(string $event): void
    {
        $this->assertFalse($this->sanitizer->isValidEventName($event));
    }

    public static function invalidEventNames(): array
    {
        return [
            'single segment' => ['object'],
            'uppercase' => ['Object.Opened'],
            'space' => ['object opened'],
            'trailing dot' => ['object.'],
            'leading dot' => ['.object'],
            'double dot' => ['object..opened'],
            'empty' => [''],
            'hyphen' => ['object.re-opened'],
        ];
    }

    public function testScalarAndScalarArrayPropertiesArePreserved(): void
    {
        $properties = [
            'count' => 5,
            'flag' => true,
            'version' => '8.4.0',
            'ratio' => 1.5,
            'missing' => null,
            // arrays of scalars are legitimate telemetry (e.g. the installed-bundle list)
            'bundles' => ['PimcoreCoreBundle', 'PimcoreDataHubBundle'],
            'nested' => ['a' => 1, 'b' => [true, false]],
        ];

        $this->assertSame($properties, $this->sanitizer->sanitizeProperties($properties, 'instance.snapshot'));
    }

    public function testObjectValuesAreDroppedAndLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning');
        $sanitizer = new EventSanitizer($logger);

        $safe = $sanitizer->sanitizeProperties([
            'count' => 3,
            'object' => new stdClass(),                 // live object -> dropped
            'nested_object' => ['ok' => 1, 'bad' => new stdClass()], // array containing an object -> dropped
        ], 'object.created');

        $this->assertSame(['count' => 3], $safe);
        $this->assertArrayNotHasKey('object', $safe);
        $this->assertArrayNotHasKey('nested_object', $safe);
    }
}
