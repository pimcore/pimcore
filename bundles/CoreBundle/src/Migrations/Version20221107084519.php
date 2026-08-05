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

final class Version20221107084519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove index column from object_url_slugs table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs DROP INDEX `index`;');
        $this->addSql('ALTER TABLE object_url_slugs DROP `index`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs ADD `index` int(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER `fieldname`;');
        $this->addSql('ALTER TABLE object_url_slugs ADD INDEX `index`(`index`);');
    }
}
