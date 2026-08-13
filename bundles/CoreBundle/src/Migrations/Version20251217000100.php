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

final class Version20251217000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove parametersPost, cookies and serverVars from http_error_log';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log')) {
            return;
        }

        $this->addSql('
            ALTER TABLE `http_error_log`
                DROP COLUMN `parametersPost`,
                DROP COLUMN `cookies`,
                DROP COLUMN `serverVars`
        ');
    }

    public function down(Schema $schema): void
    {
        // do nothing
    }
}
