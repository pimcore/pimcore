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

final class Version20220402104849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change docTypes column of user table to text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` CHANGE `docTypes` `docTypes` text DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` CHANGE `docTypes` `docTypes` varchar(255) DEFAULT NULL');
    }
}
