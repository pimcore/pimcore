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

/**
 * Sets sourceSite=0 (Main domain) for all redirects with sourceSite = NULL
 * before NULL and 0 were both treated as main domain and in fact sourceSite was not optional (although UI told so)
 */
final class Version20250526125951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign sourceSite=0 to redirects with sourceSite = NULL';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $this->addSql('UPDATE redirects SET sourceSite=0 WHERE sourceSite IS NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $this->addSql('UPDATE redirects SET sourceSite=NULL WHERE sourceSite=0');
    }
}
