<?php

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
use Pimcore\Db;

class Version20230424084415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove all "attributes" data from link editables';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('documents_editables')) {
            $db = Db::get();
            $db->executeStatement('SET foreign_key_checks = 0');

            $primaryKey = $schema->getTable('documents_editables')->getPrimaryKey()->getColumns();
            $editables = $db->fetchAllAssociative('SELECT * FROM documents_editables WHERE type = ?', ['link']);

            foreach ($editables as $editable) {
                $unserialized = unserialize($editable['data']);
                if (is_array($unserialized) && array_key_exists('attributes', $unserialized)) {
                    unset($unserialized['attributes']);

                    $editable['data'] = serialize($unserialized);

                    Db\Helper::upsert(
                        $db,
                        'documents_editables',
                        $editable,
                        $primaryKey
                    );
                }
            }
            $db->executeStatement('SET foreign_key_checks = 1');
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('Can\'t bring deleted data back ...');
    }
}
