<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Targeting\Condition;

use Pimcore\Targeting\Condition\GeoPoint;
use Pimcore\Targeting\DataProvider\GeoLocation;
use Pimcore\Targeting\Model\GeoLocation as GeoLocationModel;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers GeoPoint
 */
class GeoPointTest extends TestCase
{
    private $points = [
        // sbg - muc = ~118 km
        // sbg - ber = ~521 km
        // sbg - bkk = ~8689 km
        // muc - ber = ~500 km
        // muc - bkk = ~8795 km
        // ber - bkk = ~8602 km

        // pimcore office
        'sbg' => [
            47.83610443106286,
            13.062701225280762,
        ],

        // munich olympiapark
        'muc' => [
            48.17546460000001,
            11.551796999999965,
        ],

        // berlin alexanderplatz
        'ber' => [
            52.5219184,
            13.413214700000026,
        ],

        // bangkok grand palace
        'bkk' => [
            13.75005680956885,
            100.49125671386719,
        ],
    ];

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(GeoPoint $condition, VisitorInfo $visitorInfo, bool $expected)
    {
        $this->assertEquals($expected, $condition->match($visitorInfo));
    }

    /**
     * @dataProvider noMatchProvider
     */
    public function testCannotMatchIfOptionsEmpty(GeoPoint $condition)
    {
        $this->assertFalse($condition->canMatch());
    }

    public function matchProvider(): \Generator
    {
        yield [
            $this->createCondition('sbg', 110),
            $this->createVisitorInfo('muc'),
            false,
        ];

        yield [
            $this->createCondition('sbg', 120),
            $this->createVisitorInfo('muc'),
            true,
        ];

        yield [
            $this->createCondition('sbg', 500),
            $this->createVisitorInfo('ber'),
            false,
        ];

        yield [
            $this->createCondition('sbg', 600),
            $this->createVisitorInfo('ber'),
            true,
        ];

        yield [
            $this->createCondition('sbg', 100),
            $this->createVisitorInfo('bkk'),
            false,
        ];

        yield [
            $this->createCondition('sbg', 8000),
            $this->createVisitorInfo('bkk'),
            false,
        ];

        yield [
            $this->createCondition('sbg', 9000),
            $this->createVisitorInfo('bkk'),
            true,
        ];
    }

    public function noMatchProvider(): \Generator
    {
        yield [new GeoPoint(1.2, 2.3, null)];
        yield [new GeoPoint(1.2, null, 4)];
        yield [new GeoPoint(null, 2.3, 4)];
        yield [new GeoPoint(1.2, null, null)];
        yield [new GeoPoint(null, 2.3, null)];
        yield [new GeoPoint(null, null, 4)];
        yield [new GeoPoint(null, null, null)];
    }

    private function createCondition(string $point, int $radius): GeoPoint
    {
        if (!isset($this->points[$point])) {
            throw new \InvalidArgumentException(sprintf('Point "%s" is not defined', $point));
        }

        return new GeoPoint(
            $this->points[$point][0],
            $this->points[$point][1],
            $radius
        );
    }

    private function createVisitorInfo(string $point): VisitorInfo
    {
        if (!isset($this->points[$point])) {
            throw new \InvalidArgumentException(sprintf('Point "%s" is not defined', $point));
        }

        $geoLocation = new GeoLocationModel($this->points[$point][0], $this->points[$point][1]);

        // create visitor info and set geolocation as GeoLocation provider key
        $visitorInfo = new VisitorInfo(new Request());
        $visitorInfo->set(GeoLocation::PROVIDER_KEY, $geoLocation);

        return $visitorInfo;
    }
}
