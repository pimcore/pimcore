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
