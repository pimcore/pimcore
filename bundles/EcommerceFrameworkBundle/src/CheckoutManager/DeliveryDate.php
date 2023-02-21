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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;

/**
 * Sample implementation for delivery date
 */
class DeliveryDate extends AbstractStep implements CheckoutStepInterface
{
    const INSTANTLY = 'delivery_instantly';

    const DATE = 'delivery_date';

    public function getName(): string
    {
        return 'deliverydate';
    }

    /**
     * {@inheritdoc}
     */
    public function commit(mixed $data): bool
    {
        if (empty($data->instantly) && empty($data->date)) {
            throw new InvalidConfigException('Instantly or Date not set.');
        }

        $this->cart->setCheckoutData(self::INSTANTLY, $data->instantly);

        $date = null;
        if ($data->date instanceof \DateTime) {
            $date = $data->date->getTimestamp();
        }

        $this->cart->setCheckoutData(self::DATE, (string) $date);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): mixed
    {
        $data = new \stdClass();
        $data->instantly = $this->cart->getCheckoutData(self::INSTANTLY);

        if ($this->cart->getCheckoutData(self::DATE)) {
            $data->date = new \DateTime();
            $data->date->setTimestamp((int) $this->cart->getCheckoutData(self::DATE));
        } else {
            $data->instantly = true;
        }

        return $data;
    }
}
