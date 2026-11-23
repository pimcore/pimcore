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

namespace Pimcore\Tests\Unit\Maintenance\Tasks;

use Pimcore\Maintenance\Tasks\CleanupClassificationstoreTablesTask;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Tests\Support\Test\TestCase;

class CleanupClassificationstoreTablesTaskTest extends TestCase
{
    /**
     * A class may carry classification store fields on stores with different engines, so the sweep
     * has to reach the storage of every one of them, not only the default.
     */
    public function testEveryRegisteredEngineSweepsItsOwnStorage(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $eavEngine->expects($this->once())->method('cleanup');

        $otherEngine = $this->createMock(EngineInterface::class);
        $otherEngine->expects($this->once())->method('cleanup');

        $task = new CleanupClassificationstoreTablesTask(
            new EngineResolver(['eav' => $eavEngine, 'other' => $otherEngine])
        );

        $task->execute();
    }

    public function testWithOnlyTheDefaultEngineOnlyItSweeps(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $eavEngine->expects($this->once())->method('cleanup');

        (new CleanupClassificationstoreTablesTask(new EngineResolver(['eav' => $eavEngine])))->execute();
    }
}
