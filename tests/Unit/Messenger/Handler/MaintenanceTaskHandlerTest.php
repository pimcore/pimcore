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

namespace Pimcore\Tests\Unit\Messenger\Handler;

use PHPUnit\Framework\TestCase;
use Pimcore\Maintenance\ExecutorInterface;
use Pimcore\Messenger\Handler\MaintenanceTaskHandler;
use Pimcore\Messenger\MaintenanceTaskMessage;
use Symfony\Component\Messenger\Handler\Acknowledger;

class MaintenanceTaskHandlerTest extends TestCase
{
    public function testSynchronousInvocation(): void
    {
        $executor = $this->createMock(ExecutorInterface::class);
        $executor->expects($this->once())
            ->method('executeTask')
            ->with('my_task');

        $handler = new MaintenanceTaskHandler($executor);
        $handler(new MaintenanceTaskMessage('my_task'));
    }

    public function testBatchDeduplicatesByTaskName(): void
    {
        $executed = [];

        $executor = $this->createStub(ExecutorInterface::class);
        $executor->method('executeTask')
            ->willReturnCallback(function (string $name) use (&$executed): void {
                $executed[] = $name;
            });

        $handler = new MaintenanceTaskHandler($executor);

        $acks = [];
        $makeAck = function (string $label) use (&$acks): Acknowledger {
            $ack = new Acknowledger(MaintenanceTaskHandler::class, function (?\Throwable $e = null, mixed $result = null) {});
            $acks[$label] = $ack;

            return $ack;
        };

        $handler(new MaintenanceTaskMessage('task_a'), $makeAck('a1'));
        $handler(new MaintenanceTaskMessage('task_a'), $makeAck('a2'));
        $handler(new MaintenanceTaskMessage('task_b'), $makeAck('b1'));
        $handler(new MaintenanceTaskMessage('task_a'), $makeAck('a3'));

        $handler->flush(true);

        $this->assertSame(['task_a', 'task_b'], $executed);

        $this->assertCount(4, $acks);
        foreach ($acks as $label => $ack) {
            $this->assertTrue($ack->isAcknowledged(), "Acknowledger '$label' should be acknowledged");
            $this->assertNull($ack->getError(), "Acknowledger '$label' should have no error");
        }
    }

    public function testBatchNacksOnFailure(): void
    {
        $exception = new \RuntimeException('task failed');

        $executor = $this->createStub(ExecutorInterface::class);
        $executor->method('executeTask')
            ->willThrowException($exception);

        $handler = new MaintenanceTaskHandler($executor);

        $ack = new Acknowledger(MaintenanceTaskHandler::class, function (?\Throwable $e = null, mixed $result = null) {});

        $handler(new MaintenanceTaskMessage('failing_task'), $ack);
        $handler->flush(true);

        $this->assertTrue($ack->isAcknowledged());
        $this->assertSame($exception, $ack->getError());
    }

    public function testBatchExecutesEachUniqueTaskOnce(): void
    {
        $executed = [];

        $executor = $this->createStub(ExecutorInterface::class);
        $executor->method('executeTask')
            ->willReturnCallback(function (string $name) use (&$executed): void {
                $executed[] = $name;
            });

        $handler = new MaintenanceTaskHandler($executor);

        $acks = [];
        $makeAck = function () use (&$acks): Acknowledger {
            $ack = new Acknowledger(MaintenanceTaskHandler::class, function (?\Throwable $e = null, mixed $result = null) {});
            $acks[] = $ack;

            return $ack;
        };

        $handler(new MaintenanceTaskMessage('task_a'), $makeAck());
        $handler(new MaintenanceTaskMessage('task_b'), $makeAck());
        $handler(new MaintenanceTaskMessage('task_a'), $makeAck());
        $handler(new MaintenanceTaskMessage('task_c'), $makeAck());
        $handler(new MaintenanceTaskMessage('task_b'), $makeAck());

        $handler->flush(true);

        $this->assertSame(['task_a', 'task_b', 'task_c'], $executed);

        $this->assertCount(5, $acks);
        foreach ($acks as $i => $ack) {
            $this->assertTrue($ack->isAcknowledged(), "Acknowledger #$i should be acknowledged");
            $this->assertNull($ack->getError(), "Acknowledger #$i should have no error");
        }
    }
}
