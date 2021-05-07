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
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210430124911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes addedDateTimestamp of Cart Items to mirco seconds';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(CartItem\Dao::TABLE_NAME)) {
            $this->addSql('ALTER TABLE ecommerceframework_cartitem modify addedDateTimestamp bigint not null;');
            $this->addSql('UPDATE ecommerceframework_cartitem SET addedDateTimestamp = addedDateTimestamp * 1000000;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(CartItem\Dao::TABLE_NAME)) {
            $this->addSql('UPDATE ecommerceframework_cartitem SET addedDateTimestamp = FLOOR(addedDateTimestamp / 1000000);');
            $this->addSql('ALTER TABLE ecommerceframework_cartitem modify addedDateTimestamp int(10) not null;');
        }
    }
}
