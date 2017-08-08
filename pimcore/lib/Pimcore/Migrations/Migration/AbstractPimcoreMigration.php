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

namespace Pimcore\Migrations\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;

abstract class AbstractPimcoreMigration extends AbstractMigration implements PimcoreMigrationInterface, DryRunMigrationInterface
{
    /**
     * @var bool
     */
    private $dryRun = false;

    /**
     * @inheritdoc
     */
    public function setDryRun(bool $dryRun)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * @inheritdoc
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * @inheritdoc
     */
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * Writes a log message in the same format as a DB migration does (prefixed with ->)
     *
     * @param $message
     */
    protected function writeMessage($message)
    {
        $this->write(sprintf('     <comment>-></comment> %s', $message));
    }

    /**
     * Prefixes message with DRY-RUN: if in dry-run mode
     *
     * @param $message
     * @param string $prefix
     *
     * @return string
     */
    protected function dryRunMessage($message, $prefix = 'DRY-RUN:')
    {
        if ($this->isDryRun()) {
            $message = sprintf(
                '<fg=cyan>%s</> %s',
                $prefix,
                $message
            );
        }

        return $message;
    }
}
