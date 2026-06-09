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

final class Version20250416120333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index to versions.public"';
    }

    public function up(Schema $schema): void
    {
        $versionsTable = $schema->getTable('versions');

        if (!$versionsTable->hasIndex('public')) {
            $versionsTable->addIndex(['public']);
        }
    }

    public function down(Schema $schema): void
    {
        $versionsTable = $schema->getTable('versions');

        if ($versionsTable->hasIndex('public')) {
            $versionsTable->dropIndex('public');
        }
    }
}
