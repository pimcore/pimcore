<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use OnlineShop\Framework\Model\AbstractOrder;
use OnlineShop\Framework\Tracking\ICheckoutComplete;
use OnlineShop\Framework\Tracking\ProductAction;
use OnlineShop\Framework\Tracking\Tracker;
use OnlineShop\Framework\Tracking\Transaction;
use Pimcore\Google\Analytics;

class UniversalEcommerce extends Tracker implements ICheckoutComplete
{
    /**
     * @return string
     */
    protected function getViewScriptPrefix()
    {
        return 'analytics/universal';
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $transaction = $this->getTrackingItemBuilder()->buildCheckoutTransaction($order);
        $items       = $this->getTrackingItemBuilder()->buildCheckoutItems($order);

        $view = $this->buildView();
        $view->transaction = $transaction;
        $view->items       = $items;
        $view->calls       = $this->buildCheckoutCompleteCalls($transaction, $items);

        $result = $view->render($this->getViewScript('checkout_complete'));
        Analytics::addAdditionalCode($result, 'beforeEnd');
    }

    /**
     * @param Transaction $transaction
     * @param ProductAction[] $items
     * @return mixed
     */
    protected function buildCheckoutCompleteCalls(Transaction $transaction, array $items)
    {
        $calls = [
            'ecommerce:addTransaction' => [
                $this->transformTransaction($transaction)
            ],
            'ecommerce:addItem' => []
        ];

        foreach ($items as $item) {
            $calls['ecommerce:addItem'][] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform transaction into universal data object
     *
     * @param Transaction $transaction
     * @return array
     */
    protected function transformTransaction(Transaction $transaction)
    {
        return $this->filterNullValues([
            'id'          => $transaction->getId(),                     // Transaction ID. Required.
            'affiliation' => $transaction->getAffiliation() ?: '',      // Affiliation or store name.
            'revenue'     => $transaction->getTotal(),                  // Grand Total.
            'shipping'    => $transaction->getShipping(),               // Shipping.
            'tax'         => $transaction->getTax()                     // Tax.
        ]);
    }

    /**
     * Transform product action into universal data object
     *
     * @param ProductAction $item
     * @return array
     */
    protected function transformProductAction(ProductAction $item)
    {
        return $this->filterNullValues([
            'id'       => $item->getTransactionId(),                    // Transaction ID. Required.
            'sku'      => $item->getId(),                               // SKU/code.
            'name'     => $item->getName(),                             // Product name. Required.
            'category' => $item->getCategory(),                         // Category or variation.
            'price'    => $item->getPrice(),                            // Unit price.
            'quantity' => $item->getQuantity() ?: 1,                    // Quantity.
        ]);
    }
}
