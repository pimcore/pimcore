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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210428145320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increases prettyUrl field size to 255';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `documents_page` CHANGE `prettyUrl` `prettyUrl` varchar(255) NULL AFTER `metaData`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `documents_page` CHANGE `prettyUrl` `prettyUrl` varchar(190) NULL AFTER `metaData`;");
    }
}
