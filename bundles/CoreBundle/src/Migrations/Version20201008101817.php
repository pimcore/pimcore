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

/**
 * @internal
 */
final class Version20201008101817 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS documents_editables;');

        if ($schema->hasTable('documents_elements')) {
            $this->addSql('RENAME TABLE documents_elements TO documents_editables;');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE documents_editables TO documents_elements;');
        $this->addSql('CREATE OR REPLACE VIEW documents_editables AS SELECT * FROM documents_elements;');
    }
}
