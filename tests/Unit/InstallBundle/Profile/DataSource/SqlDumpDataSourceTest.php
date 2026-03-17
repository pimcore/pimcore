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

namespace Pimcore\Tests\Unit\InstallBundle\Profile\DataSource;

use Pimcore\Bundle\InstallBundle\Profile\DataSource\SqlDumpDataSource;
use Pimcore\Tests\Support\Test\TestCase;

final class SqlDumpDataSourceTest extends TestCase
{
    public function testGetLabelReturnsDirectoryBasename(): void
    {
        $dataSource = new SqlDumpDataSource('/path/to/some/dump-dir');

        $this->assertSame('SQL dumps from dump-dir', $dataSource->getLabel());
    }

    public function testGetLabelWithDifferentPath(): void
    {
        $dataSource = new SqlDumpDataSource('/var/data/pimcore-install');

        $this->assertSame(
            'SQL dumps from pimcore-install',
            $dataSource->getLabel(),
        );
    }

    public function testConstructorWithCustomMarkerTable(): void
    {
        $dataSource = new SqlDumpDataSource(
            '/path/to/dumps',
            '_custom_marker_table',
        );

        $this->assertSame('SQL dumps from dumps', $dataSource->getLabel());
    }
}
