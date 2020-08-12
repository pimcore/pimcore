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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Exception;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;

class PaymentNotSuccessfulException extends AbstractEcommerceException
{
    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * @var StatusInterface
     */
    protected $status;

    /**
     * PaymentNotSuccessfulException constructor.
     *
     * @param AbstractOrder $order
     * @param StatusInterface $status
     * @param string $message
     */
    public function __construct(AbstractOrder $order, StatusInterface $status, string $message)
    {
        parent::__construct($message);
        $this->order = $order;
        $this->status = $status;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder(): AbstractOrder
    {
        return $this->order;
    }

    /**
     * @return StatusInterface
     */
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }
}
