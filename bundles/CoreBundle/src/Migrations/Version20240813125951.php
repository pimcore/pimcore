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
 * Modifies foreign key constraints for specified tables, changing their names from ending in '_o_id' to '_id' and adding ON DELETE CASCADE.
 * The down method reverses the naming back to '_o_id' while keeping ON DELETE CASCADE.
 */
final class Version20240813125951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign sourceSite=0 to redirects with sourceSite = NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE redirects SET sourceSite=0 WHERE sourceSite IS NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
