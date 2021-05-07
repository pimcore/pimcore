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
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200407145422 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('notes');
        if ($table->hasIndex('cid')) {
            $table->dropIndex('cid');
        }

        if ($table->hasIndex('ctype')) {
            $table->dropIndex('ctype');
        }

        if (!$table->hasIndex('cid_ctype')) {
            $table->addIndex(['cid', 'ctype'], 'cid_ctype');
        }

        if (!$table->hasIndex('user')) {
            $table->addIndex(['user'], 'user');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('notes');
        if (!$table->hasIndex('cid')) {
            $table->addIndex(['cid'], 'cid');
        }

        if (!$table->hasIndex('ctype')) {
            $table->addIndex(['ctype'], 'ctype');
        }

        if ($table->hasIndex('cid_ctype')) {
            $table->dropIndex('cid_ctype');
        }

        if ($table->hasIndex('user')) {
            $table->dropIndex('user');
        }
    }
}
