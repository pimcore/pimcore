<?php

declare(strict_types=1);

namespace Tests\AppBundle;

use PHPUnit\Framework\TestCase;

/**
 * This test is just a dummy for demonstration purposes and
 * doesn't actually test any class.
 */
class ExampleTest extends TestCase
{
    public function testPhpCanCalculate()
    {
        $this->assertEquals(15, 10 + 5);
        $this->assertEquals(100, pow(10, 2));
    }

    /**
     * @dataProvider addDataProvider
     *
     * @param int $a
     * @param int $b
     * @param int $expected
     */
    public function testPhpCanAddWithProvider(int $a, int $b, int $expected)
    {
        $this->assertEquals($expected, $a + $b, sprintf('%d + %d = %d', $a, $b, $expected));
    }

    public function testSomethingElse()
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $obj1->obj = $obj3;
        $obj2->obj = $obj3;

        $this->assertNotNull($obj1);
        $this->assertNotNull($obj2);
        $this->assertNotNull($obj3);

        $this->assertNotSame($obj1, $obj2);
        $this->assertSame($obj1->obj, $obj2->obj);
        $this->assertSame($obj3, $obj1->obj);
        $this->assertSame($obj3, $obj2->obj);
    }

    public function testException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This test is about to fail');

        throw new \RuntimeException('This test is about to fail');
    }

    public function addDataProvider(): array
    {
        return [
            [1, 2, 3],
            [10, 5, 15],
            [-5, 5, 0],
            [5, -5, 0],
            [0, 10, 10],
            [-50, -50, -100],
            [-50, 10, -40]
        ];
    }
}
