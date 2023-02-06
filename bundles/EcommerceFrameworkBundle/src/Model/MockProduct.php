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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\Concrete;

/**
 * Mock Product class which should be used as a product when actual product is not available in the System.
 */
class MockProduct extends Concrete implements ProductInterface, IndexableInterface, CheckoutableInterface
{
    public function getAvailabilitySystemName(): string
    {
        return 'default';
    }

    public function getOSIsBookable(int $quantityScale = 1): bool
    {
        return false;
    }

    public function getPriceSystemImplementation(): PriceSystemInterface
    {
        return Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    public function getAvailabilitySystemImplementation(): AvailabilitySystemInterface
    {
        return Factory::getInstance()->getAvailabilitySystem($this->getAvailabilitySystemName());
    }

    public function getOSPrice(int $quantityScale = 1): PriceInterface
    {
        /** @var EnvironmentInterface $environment */
        $environment = \Pimcore::getKernel()->getContainer()->get(EnvironmentInterface::class);

        return new Price(Decimal::create(0), $environment->getDefaultCurrency());
    }

    public function getOSPriceInfo(int $quantityScale = 1): PriceInfoInterface
    {
        return $this->getPriceSystemImplementation()->getPriceInfo($this, 0);
    }

    public function getOSAvailabilityInfo(int $quantity = null): AvailabilityInterface
    {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, 0);
    }

    public function getOSDoIndexProduct(): bool
    {
        return false;
    }

    public function getPriceSystemName(): string
    {
        return 'default';
    }

    public function isActive(bool $inProductList = false): bool
    {
        return false;
    }

    public function getOSIndexType(): ?string
    {
        return null;
    }

    public function getOSParentId(): int|null
    {
        return null;
    }

    public function getCategories(): ?array
    {
        return null;
    }

    public function getOSName(): ?string
    {
        return 'Product Not Available';
    }

    public function getOSProductNumber(): ?string
    {
        return null;
    }

    public function getPrice(): int
    {
        return 0;
    }

    public function __call(string $method, array $args): mixed
    {
        return null;
    }
}
