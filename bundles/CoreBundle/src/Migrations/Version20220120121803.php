<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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

        $this->addSql('ALTER TABLE `documents_hardlink`
            ADD CONSTRAINT `fk_documents_hardlink_documents`FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_link`
            ADD CONSTRAINT `fk_documents_link_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_page`
            ADD CONSTRAINT `fk_documents_page_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_snippet`
            ADD CONSTRAINT `fk_documents_snippet_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_printpage`
            ADD CONSTRAINT `fk_documents_printpage_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_email`
            ADD CONSTRAINT `fk_documents_email_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `email_log`
            CHANGE `documentId` `documentId` int(11) unsigned NULL,
            ADD CONSTRAINT `fk_email_log_documents`
            FOREIGN KEY (`documentId`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_newsletter`
            ADD CONSTRAINT `fk_documents_newsletter_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_editables`
            ADD CONSTRAINT `fk_documents_editables_documents`
            FOREIGN KEY (`documentId`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `documents_translations`
            ADD CONSTRAINT `fk_documents_translations_documents`
            FOREIGN KEY (`id`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `sites`
            ADD CONSTRAINT `fk_sites_documents`
            FOREIGN KEY (`rootId`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        //enable foreign key checks
        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `documents_hardlink` DROP FOREIGN KEY IF EXISTS `fk_documents_hardlink_documents`;');

        $this->addSql('ALTER TABLE `documents_link` DROP FOREIGN KEY IF EXISTS `fk_documents_link_documents`;');

        $this->addSql('ALTER TABLE `documents_page` DROP FOREIGN KEY IF EXISTS `fk_documents_page_documents`;');

        $this->addSql('ALTER TABLE `documents_snippet` DROP FOREIGN KEY IF EXISTS `fk_documents_snippet_documents`;');

        $this->addSql('ALTER TABLE `documents_printpage` DROP FOREIGN KEY IF EXISTS `fk_documents_printpage_documents`;');

        $this->addSql('ALTER TABLE `documents_snippet` DROP FOREIGN KEY IF EXISTS `fk_documents_snippet_documents`;');

        $this->addSql('ALTER TABLE `documents_email` DROP FOREIGN KEY IF EXISTS `fk_documents_email_documents`;');

        $this->addSql('ALTER TABLE `email_log` DROP FOREIGN KEY IF EXISTS `fk_email_log_documents`;');

        $this->addSql('ALTER TABLE `documents_newsletter` DROP FOREIGN KEY IF EXISTS `fk_documents_newsletter_documents`;');

        $this->addSql('ALTER TABLE `documents_editables` DROP FOREIGN KEY IF EXISTS `fk_documents_editables_documents`;');

        $this->addSql('ALTER TABLE `documents_translations` DROP FOREIGN KEY IF EXISTS `fk_documents_translations_documents`;');

        $this->addSql('ALTER TABLE `sites` DROP FOREIGN KEY IF EXISTS `fk_sites_documents`;');
    }
}
