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

final class Version20230222174636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('documents_page')->hasColumn('metaData')) {
            $this->addSql('ALTER TABLE documents_page DROP COLUMN metaData');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('documents_page')->hasColumn('metaData')) {
            $this->addSql('ALTER TABLE documents_page ADD COLUMN `metaData` TEXT AFTER `description`');
        }
    }
}
