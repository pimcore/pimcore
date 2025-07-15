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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210624085031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support saving error message for sent mails';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('email_log')->hasColumn('error')) {
            $this->addSql('ALTER TABLE email_log ADD error TEXT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('email_log')->hasColumn('error')) {
            $this->addSql('ALTER TABLE email_log DROP error');
        }
    }
}
