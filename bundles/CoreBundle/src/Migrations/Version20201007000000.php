<?php

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

use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

/**
 * @internal
 */
final class Version20201007000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $db = Db::get();
        $cacheAdapter = new DoctrineDbalAdapter($db);

        try {
            $cacheAdapter->createTable();
        } catch (TableExistsException) {
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cache_items');
    }
}
