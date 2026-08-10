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
        $this->assertSame('11-50', $this->bucketizer->bucket(11));
        $this->assertSame('11-50', $this->bucketizer->bucket(50));
        $this->assertSame('51-200', $this->bucketizer->bucket(51));
        $this->assertSame('51-200', $this->bucketizer->bucket(200));
        $this->assertSame('201-1000', $this->bucketizer->bucket(201));
        $this->assertSame('201-1000', $this->bucketizer->bucket(1000));
        $this->assertSame('1000+', $this->bucketizer->bucket(1001));
        $this->assertSame('1000+', $this->bucketizer->bucket(50000));
    }
}
