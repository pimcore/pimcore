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

class Version20200410112354 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('properties');
        if ($table->hasIndex('cpath')) {
            $table->dropIndex('cpath');
        }

        if ($table->hasIndex('inheritable')) {
            $table->dropIndex('inheritable');
        }

        if ($table->hasIndex('ctype')) {
            $table->dropIndex('ctype');
        }

        if (!$table->hasIndex('getall')) {
            // inheritable should be the last one as not used here:
            // https://github.com/pimcore/pimcore/blob/1f9fca52eacf4fcc8b8c7899b65c0b1900db9124/models/Document/Dao.php#L259
            $table->addIndex(['cpath', 'ctype', 'inheritable'], 'getall');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('properties');
        if (!$table->hasIndex('cpath')) {
            $table->addIndex(['cpath'], 'cpath');
        }

        if (!$table->hasIndex('inheritable')) {
            $table->addIndex(['inheritable'], 'inheritable');
        }

        if (!$table->hasIndex('ctype')) {
            $table->addIndex(['ctype'], 'ctype');
        }

        if ($table->hasIndex('getall')) {
            $table->dropIndex('getall');
        }
    }
}
