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

class Version20190807121356 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $ecommerceTranslationUpdates = [
            'bundle_ecommerce.back-office.order-list.total-orders' => 'Total Orders',
            'bundle_ecommerce.back-office.order.cart-taxes' => 'Cart Taxes',
            'bundle_ecommerce.back-office.order.customer-account.orderCount' => 'Order Count',
            'bundle_ecommerce.back-office.order-list.filter-date.to' => 'To Date',
        ];

        foreach ($ecommerceTranslationUpdates as $key => $value) {
            $translation = \Pimcore\Model\Translation\Admin::getByKey($key);

            if (!$translation instanceof \Pimcore\Model\Translation\Admin) {
                $translation = new \Pimcore\Model\Translation\Admin();
                $translation->setKey($key);
                $translation->setCreationDate(time());
                $translation->setModificationDate(time());
                $translation->addTranslation('en', $value);
                $translation->save();
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $ecommerceTranslationUpdates = [
            'bundle_ecommerce.back-office.order-list.total-orders' => 'Total Orders',
            'bundle_ecommerce.back-office.order.cart-taxes' => 'Cart Taxes',
            'bundle_ecommerce.back-office.order.customer-account.orderCount' => 'Order Count',
            'bundle_ecommerce.back-office.order-list.filter-date.to' => 'To Date',
        ];

        foreach ($ecommerceTranslationUpdates as $key => $value) {
            $translation = \Pimcore\Model\Translation\Admin::getByKey($key);
            if ($translation instanceof \Pimcore\Model\Translation\Admin) {
                $translation->delete();
            }
        }
    }
}
