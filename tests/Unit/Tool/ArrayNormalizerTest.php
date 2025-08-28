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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\ArrayNormalizer;

class ArrayNormalizerTest extends TestCase
{
    private array $input = [
        'a' => 'foo',
        'b' => 'bar',
        'c' => 'baz',
        'd' => 'inga',
    ];

    public function testArrayIsUntouchedWithoutNormalizers(): void
    {
        $normalizer = new ArrayNormalizer();
        $result = $normalizer->normalize($this->input);

        $this->assertEquals($this->input, $result);
    }

    public function testNormalizerNormalizesValues(): void
    {
        $normalizer = new ArrayNormalizer();

        // add as array
        $normalizer->addNormalizer(['a', 'b'], function ($value, $key, $values) {
            return 'normalized:' . $value;
        });

        // add as single property
        $normalizer->addNormalizer('c', function ($value, $key, $values) {
            return 'normalized2:' . $value;
        });

        $result = $normalizer->normalize($this->input);

        $this->assertEquals(
            [
                'a' => 'normalized:foo',
                'b' => 'normalized:bar',
                'c' => 'normalized2:baz',
                'd' => 'inga',
            ],
            $result
        );
    }

    public function testNormalizerPassesKeyAndWholeArrayToNormalizerFunction(): void
    {
        $normalizer = new ArrayNormalizer();

        // add as array
        $normalizer->addNormalizer(['a', 'b'], function ($value, $key, $values) {
            $this->assertTrue(in_array($key, ['a', 'b']));
            $this->assertEquals($this->input[$key], $value);
            $this->assertEquals($this->input, $values);

            return $value;
        });

        $result = $normalizer->normalize($this->input);

        $this->assertEquals($this->input, $result);
    }
}
