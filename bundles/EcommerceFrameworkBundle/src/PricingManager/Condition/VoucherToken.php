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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token as VoucherServiceToken;
use Pimcore\Model\DataObject\Concrete;

class VoucherToken implements ConditionInterface
{
    /**
     * @var int[]
     */
    protected array $whiteListIds = [];

    /**
     * @var \stdClass[]
     */
    protected array $whiteList = [];

    /**
     * @var string[]
     */
    protected array $errorMessages = [];

    public function check(EnvironmentInterface $environment): bool
    {
        if (!($cart = $environment->getCart())) {
            return false;
        }

        $voucherTokenCodes = $cart->getVoucherTokenCodes();

        if (is_array($voucherTokenCodes)) {
            foreach ($voucherTokenCodes as $code) {
                if ($this->checkVoucherCode($code)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function checkVoucherCode(string $code): bool
    {
        if (in_array(VoucherServiceToken::getByCode($code)->getVoucherSeriesId(), $this->whiteListIds)) {
            return true;
        }

        return false;
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'VoucherToken',
            'whiteList' => [],
            'error_messages' => $this->getErrorMessagesRaw(),
        ];

        // add categories
        foreach ($this->getWhiteList() as $series) {
            $json['whiteList'][] = [
                $series->id,
                $series->path,
            ];
        }

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $whiteListIds = [];
        $whiteList = [];

        foreach ($json->whiteList as $series) {
            $seriesId = $series->id;
            if ($seriesId) {
                $whiteListIds[] = $seriesId;
                $whiteList[] = $series;
            }
        }

        $this->setErrorMessagesRaw((array)$json->error_messages);

        $this->setWhiteListIds($whiteListIds);
        $this->setWhiteList($whiteList);

        return $this;
    }

    protected function loadSeries(int $id): ?Concrete
    {
        return Concrete::getById($id);
    }

    /**
     * @return int[]
     */
    public function getWhiteListIds(): array
    {
        return $this->whiteListIds;
    }

    /**
     * @param int[] $whiteListIds
     */
    public function setWhiteListIds(array $whiteListIds): void
    {
        $this->whiteListIds = $whiteListIds;
    }

    /**
     * @return \stdClass[]
     */
    public function getWhiteList(): array
    {
        return $this->whiteList;
    }

    /**
     * @param \stdClass[] $whiteList
     */
    public function setWhiteList(array $whiteList): void
    {
        $this->whiteList = $whiteList;
    }

    /**
     * @return string[]
     */
    public function getErrorMessagesRaw(): array
    {
        return $this->errorMessages;
    }

    /**
     * @param string[] $errorMessages
     */
    public function setErrorMessagesRaw(array $errorMessages): void
    {
        $this->errorMessages = $errorMessages;
    }

    public function getErrorMessage(string $locale): string
    {
        return $this->errorMessages[$locale];
    }
}
