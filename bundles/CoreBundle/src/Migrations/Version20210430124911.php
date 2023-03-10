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
        $this->warnIf(
            $schema->hasTable(CartItem\Dao::TABLE_NAME),
            sprintf('Unable to migrate %s as the EcommerceFramework being moved in own bundle since Pimcore 11. Please execute "Pimcore\Bundle\EcommerceFrameworkBundle\%s" instead', self::class, self::class)
        );
    }

    public function down(Schema $schema): void
    {
        $this->warnIf(
            $schema->hasTable(CartItem\Dao::TABLE_NAME),
            sprintf('Unable to migrate %s as the EcommerceFramework being moved in own bundle since Pimcore 11. Please execute "Pimcore\Bundle\EcommerceFrameworkBundle\%s" instead', self::class, self::class)
        );
    }
}
