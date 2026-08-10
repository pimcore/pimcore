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

use Pimcore\Telemetry\Snapshot\Bucketizer;
use Pimcore\Tests\Support\Test\TestCase;

class BucketizerTest extends TestCase
{
    private Bucketizer $bucketizer;

    protected function setUp(): void
    {
        $this->bucketizer = new Bucketizer();
    }

    public function testBucketBoundaries(): void
    {
        $this->assertSame('0', $this->bucketizer->bucket(0));
        $this->assertSame('0', $this->bucketizer->bucket(-5));
        $this->assertSame('1-10', $this->bucketizer->bucket(1));
        $this->assertSame('1-10', $this->bucketizer->bucket(10));
        $this->assertSame('11-100', $this->bucketizer->bucket(11));
        $this->assertSame('11-100', $this->bucketizer->bucket(100));
        $this->assertSame('101-500', $this->bucketizer->bucket(101));
        $this->assertSame('101-500', $this->bucketizer->bucket(500));
        $this->assertSame('501-1000', $this->bucketizer->bucket(501));
        $this->assertSame('501-1000', $this->bucketizer->bucket(1000));
        $this->assertSame('1001-10000', $this->bucketizer->bucket(1001));
        $this->assertSame('1001-10000', $this->bucketizer->bucket(10000));
        $this->assertSame('10001-100000', $this->bucketizer->bucket(10001));
        $this->assertSame('10001-100000', $this->bucketizer->bucket(100000));
        $this->assertSame('100001-1000000', $this->bucketizer->bucket(100001));
        $this->assertSame('100001-1000000', $this->bucketizer->bucket(1000000));
        $this->assertSame('1000000+', $this->bucketizer->bucket(1000001));
    }

    /**
     * The top bucket is open-ended on purpose: enterprise catalogs run into the millions, and a scale
     * that saturated earlier would report the largest and the merely-large installations as the same
     * size - which is the specific thing the fleet view needs to distinguish.
     */
    public function testTheTopBucketIsOpenEnded(): void
    {
        $this->assertSame('1000000+', $this->bucketizer->bucket(5_000_000));
        $this->assertSame('1000000+', $this->bucketizer->bucket(250_000_000));
    }

    /**
     * Each of these is the last value of its bucket, so the next one up must land elsewhere. Pins the
     * boundaries themselves - including the 500 split inside the 101-1000 decade, which is the one
     * place the scale departs from powers of ten.
     */
    public function testEveryBoundaryEndsItsBucket(): void
    {
        foreach ([10, 100, 500, 1000, 10000, 100000, 1000000] as $boundary) {
            $this->assertNotSame(
                $this->bucketizer->bucket($boundary),
                $this->bucketizer->bucket($boundary + 1),
                sprintf('a bucket must end at %d', $boundary)
            );
        }
    }

    /**
     * The arms of the match are evaluated top-down, so they have to stay in ascending order. Getting
     * that wrong fails nothing loudly - it just makes a bucket unreachable and silently re-labels every
     * instance that belonged in it. Walking the scale upwards catches exactly that: a count may keep
     * its bucket or move to a new one, but it must never fall back into a bucket already left behind.
     */
    public function testBucketsOnlyEverMoveForwardAsTheCountGrows(): void
    {
        $seen = [];
        $previous = null;

        foreach ([0, 1, 10, 11, 100, 101, 500, 501, 1000, 1001, 10000, 10001,
                  100000, 100001, 1000000, 1000001, 50000000] as $count) {
            $bucket = $this->bucketizer->bucket($count);

            if ($bucket === $previous) {
                continue;
            }

            $this->assertNotContains(
                $bucket,
                $seen,
                sprintf('bucket "%s" is revisited at %d - the match arms are out of order', $bucket, $count)
            );

            $seen[] = $bucket;
            $previous = $bucket;
        }

        $this->assertCount(9, $seen, 'the scale has nine buckets; every one must be reachable');
    }
}
