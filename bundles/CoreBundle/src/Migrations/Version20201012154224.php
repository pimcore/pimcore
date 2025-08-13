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
 * @internal
 */
final class Version20201012154224 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->getTable('glossary')->hasColumn('acronym')) {
            $this->addSql('ALTER TABLE glossary DROP COLUMN acronym');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE glossary ADD COLUMN `acronym` varchar(255) DEFAULT NULL');
    }
}
