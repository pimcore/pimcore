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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

class JsonResponse extends AbstractResponse
{
    protected string $jsonString;

    /**
     * JsonResponse constructor.
     *
     * @param AbstractOrder $order
     * @param string $jsonString
     */
    public function __construct(AbstractOrder $order, string $jsonString)
    {
        parent::__construct($order);
        $this->jsonString = $jsonString;
    }

    public function getJsonString(): string
    {
        return $this->jsonString;
    }
}
