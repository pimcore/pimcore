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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260319134454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove http_error_log (irreversible; table must be recreated manually if needed)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log')) {
            return;
        }
        $this->addSql('DROP TABLE IF EXISTS http_error_log');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('This migration drops the legacy http_error_log table and cannot be reversed automatically. Please recreate the table manually using the 12.x install.sql definition if a rollback is required.');
    }
}
