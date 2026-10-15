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

final class Version20261015100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the single-column cid index on schedule_tasks with a composite (cid, ctype) index';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('schedule_tasks');

        if (!$table->hasIndex('cid_ctype')) {
            $table->addIndex(['cid', 'ctype'], 'cid_ctype');
        }

        if ($table->hasIndex('cid')) {
            $table->dropIndex('cid');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('schedule_tasks');

        if (!$table->hasIndex('cid')) {
            $table->addIndex(['cid'], 'cid');
        }

        if ($table->hasIndex('cid_ctype')) {
            $table->dropIndex('cid_ctype');
        }
    }
}
