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
 * @internal
 */
final class Version20211028155535 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs MODIFY slug VARCHAR(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs MODIFY slug VARCHAR(765) CHARACTER SET utf8 COLLATE utf8_bin;');
    }
}
