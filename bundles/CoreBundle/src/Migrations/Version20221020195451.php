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

final class Version20221020195451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace childs with children in configs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE gridconfigs SET config=REPLACE(config, \'"childs":\', \'"children":\') ;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE gridconfigs SET config=REPLACE(config, \'"children":\', \'"childs":\') ;');
    }
}
