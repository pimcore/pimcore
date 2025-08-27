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
final class Version20210428145320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increases prettyUrl field size to 255';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `documents_page` CHANGE `prettyUrl` `prettyUrl` varchar(255) NULL AFTER `metaData`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `documents_page` CHANGE `prettyUrl` `prettyUrl` varchar(190) NULL AFTER `metaData`;');
    }
}
