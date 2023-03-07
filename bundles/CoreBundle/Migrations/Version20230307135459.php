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
use Pimcore\Model\DataObject\Concrete\Dao\InheritanceHelper;

final class Version20230307135459 extends AbstractMigration
{
    protected string $fieldname = 'fieldname';

    public function getDescription(): string
    {
        return 'Add fieldname index to object_relations_ tables';
    }

    public function up(Schema $schema): void
    {
        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table->getName(), InheritanceHelper::RELATION_TABLE) && !$table->hasIndex($this->fieldname)) {
                $table->addIndex([$this->fieldname], $this->fieldname);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table->getName(), InheritanceHelper::RELATION_TABLE) && $table->hasIndex($this->fieldname)) {
                $table->dropIndex($this->fieldname);
            }
        }
    }
}
