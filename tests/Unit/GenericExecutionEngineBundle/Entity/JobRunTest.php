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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Tests\Support\Test\TestCase;

class JobRunTest extends TestCase
{
    /**
     * A freshly created job run never has a context, so it is persisted as NULL.
     * The installer creates the column as nullable, therefore the mapping must
     * be nullable as well - otherwise a schema update turns the column into
     * NOT NULL and every job run creation fails.
     */
    public function testContextColumnIsMappedAsNullable(): void
    {
        $metadata = new ClassMetadata(JobRun::class);
        (new AttributeDriver([]))->loadMetadataForClass(JobRun::class, $metadata);

        $this->assertTrue($metadata->isNullable('context'));
    }

    public function testContextIsNullByDefault(): void
    {
        $this->assertNull((new JobRun())->getContext());
    }
}
