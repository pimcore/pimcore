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

namespace Pimcore\Bundle\InstallBundle\Profile\DataSource;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

interface DataSourceInterface
{
    /** Human-readable label for CLI output */
    public function getLabel(): string;

    /**
     * Apply the data source to the installation.
     * Must be idempotent — safe to re-run on a partially applied state.
     *
     * @throws RuntimeException if the data source cannot be applied
     */
    public function apply(Connection $connection, OutputInterface $output): void;

    /**
     * Whether this data source has already been applied.
     * Used by checkpoint/resume logic.
     */
    public function isApplied(Connection $connection): bool;
}
