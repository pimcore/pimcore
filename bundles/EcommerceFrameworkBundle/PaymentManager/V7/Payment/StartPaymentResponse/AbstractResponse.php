<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

abstract class AbstractResponse implements StartPaymentResponseInterface
{
    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * AbstractResponse constructor.
     *
     * @param AbstractOrder $order
     */
    public function __construct(AbstractOrder $order)
    {
        $this->order = $order;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder(): AbstractOrder
    {
        return $this->order;
    }
}
