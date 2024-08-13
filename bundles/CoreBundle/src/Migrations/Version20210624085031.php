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
