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

final class Version20220120121803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use foreign key for delete cascade on documents references';
    }

    public function up(Schema $schema): void
    {
        //disable foreign key checks
        $this->addSql('SET foreign_key_checks = 0');

        foreach (['documents_hardlink', 'documents_link', 'documents_page', 'documents_snippet', 'documents_printpage', 'documents_email', 'documents_newsletter', 'documents_translations'] as $table) {
            if (!$schema->getTable($table)->hasForeignKey('fk_'.$table.'_documents')) {
                $this->addSql(
                    'ALTER TABLE `'.$table.'`
                    ADD CONSTRAINT `fk_'.$table.'_documents`
                    FOREIGN KEY (`id`)
                    REFERENCES `documents` (`id`)
                    ON UPDATE NO ACTION
                    ON DELETE CASCADE;'
                );
            }
        }

        if (!$schema->getTable($table)->hasForeignKey('fk_documents_editables_documents')) {
            $this->addSql(
                'ALTER TABLE `documents_editables`
                ADD CONSTRAINT `fk_documents_editables_documents`
                FOREIGN KEY (`documentId`)
                REFERENCES `documents` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        $this->addSql('ALTER TABLE `email_log` CHANGE `documentId` `documentId` int(11) unsigned NULL;');
        if (!$schema->getTable('email_log')->hasForeignKey('fk_email_log_documents')) {
            $this->addSql(
                'ALTER TABLE `email_log`
                ADD CONSTRAINT `fk_email_log_documents`
                FOREIGN KEY (`documentId`)
                REFERENCES `documents` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        if (!$schema->getTable('sites')->hasForeignKey('fk_sites_documents')) {
            $this->addSql(
                'ALTER TABLE `sites`
                ADD CONSTRAINT `fk_sites_documents`
                FOREIGN KEY (`rootId`)
                REFERENCES `documents` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        //enable foreign key checks
        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        foreach (['documents_hardlink', 'documents_link', 'documents_page', 'documents_snippet', 'documents_printpage', 'documents_email', 'email_log', 'documents_newsletter', 'documents_editables', 'documents_translations'] as $table) {
            if ($schema->getTable($table)->hasForeignKey('fk_'.$table.'_documents')) {
                $this->addSql('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `fk_'.$table.'_documents`;');
            }
        }

        if ($schema->getTable('sites')->hasForeignKey('fk_sites_documents')) {
            $this->addSql('ALTER TABLE `sites` DROP FOREIGN KEY `fk_sites_documents`;');
        }
    }
}
