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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ICheckoutComplete;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Tracker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Transaction;
use Pimcore\Google\Analytics;

class Ecommerce extends Tracker implements ICheckoutComplete
{
    /**
     * @return string
     */
    protected function getViewScriptPrefix()
    {
        return 'analytics/classic';
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $transaction = $this->getTrackingItemBuilder()->buildCheckoutTransaction($order);
        $items = $this->getTrackingItemBuilder()->buildCheckoutItems($order);

        $parameterBag['transaction'] = $transaction;
        $parameterBag['items'] = $items;
        $parameterBag['calls'] = $this->buildCheckoutCompleteCalls($transaction, $items);

        $result = $this->renderer->render($this->getViewScript('checkout_complete'), $parameterBag);
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
            $this->transformTransaction($transaction)
        ];

        foreach ($items as $item) {
            $calls[] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform transaction into classic analytics data array
     *
     * @note city, state, country were dropped as they were optional and never used
     * @param Transaction $transaction
     * @return array
     */
    protected function transformTransaction(Transaction $transaction)
    {
        return [
            '_addTrans',
            $transaction->getId(),                  // order ID - required
            $transaction->getAffiliation() ?: '',   // affiliation or store name
            $transaction->getTotal(),               // total - required
            $transaction->getTax(),                 // tax
            $transaction->getShipping(),            // shipping
        ];
    }

    /**
     * Transform product action into classic analytics data array
     *
     * @param ProductAction $item
     * @return array
     */
    protected function transformProductAction(ProductAction $item)
    {
        return [
            '_addItem',
            $item->getTransactionId(),              // transaction ID - necessary to associate item with transaction
            $item->getId(),                         // SKU/code - required
            $item->getName(),                       // product name
            $item->getCategory(),                   // category or variation
            $item->getPrice(),                      // unit price - required
            $item->getQuantity() ?: 1,              // quantity - required
        ];
    }
}
