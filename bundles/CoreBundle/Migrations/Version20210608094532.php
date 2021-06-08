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

final class Version20210608094532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change asset json files type to "text" from "unknown"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE assets SET `type` = 'text' WHERE `mimetype` = 'application/json';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE assets SET `type` = 'unknown' WHERE `mimetype` = 'application/json';");
    }
}
