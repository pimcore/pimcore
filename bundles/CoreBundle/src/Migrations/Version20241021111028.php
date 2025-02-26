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

final class Version20241021111028 extends AbstractMigration
{
    protected array $tables = [
        'assets',
        'documents',
        'objects',
        'versions',
    ];

    public function getDescription(): string
    {
        return 'Add versionCount index to elements and versions table';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->tables as $table) {
            $dbTable = $schema->getTable($table);
            if (!$dbTable->hasIndex('versionCount')) {
                $dbTable->addIndex(['versionCount'], 'versionCount');
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->tables as $table) {
            $dbTable = $schema->getTable($table);
            if ($dbTable->hasIndex('versionCount')) {
                $dbTable->dropIndex('versionCount');
            }
        }
    }
}
