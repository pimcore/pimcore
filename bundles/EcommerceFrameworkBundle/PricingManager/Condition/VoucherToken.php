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
    protected $allowListIds = [];

    /**
     * @var \stdClass[]
     */
    protected $allowList = [];

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @var int[]
     */
    protected $whiteListIds = [];

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @var \stdClass[]
     */
    protected $whiteList = [];

    /**
     * @var string[]
     */
    protected $errorMessages = [];

    /**
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment)
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

    /**
     * @param string $code
     *
     * @return bool
     */
    public function checkVoucherCode($code)
    {
        if (in_array(VoucherServiceToken::getByCode($code)->getVoucherSeriesId(), $this->allowListIds)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'VoucherToken',
            'allowList' => [],
            'error_messages' => $this->getErrorMessagesRaw(),
        ];

        // add categories
        foreach ($this->getAllowList() as $series) {
            $json['allowList'][] = [
                $series->id,
                $series->path,
            ];
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ConditionInterface
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $allowListIds = [];
        $allowList = [];

        foreach ($json->allowList as $series) {
            $seriesId = $series->id;
            if ($seriesId) {
                $allowListIds[] = $seriesId;
                $allowList[] = $series;
            }
        }

        $this->setErrorMessagesRaw((array)$json->error_messages);

        $this->setAllowListIds($allowListIds);
        $this->setAllowList($allowList);

        return $this;
    }

    /**
     * @param int $id
     *
     * @return Concrete|null
     */
    protected function loadSeries($id)
    {
        return Concrete::getById($id);
    }

    /**
     * @return int[]
     */
    public function getAllowListIds()
    {
        return $this->allowListIds;
    }

    /**
     * @param int[] $allowListIds
     */
    public function setAllowListIds($allowListIds)
    {
        $this->allowListIds = $allowListIds;
    }

    /**
     * @return \stdClass[]
     */
    public function getAllowList()
    {
        return $this->allowList;
    }

    /**
     * @param \stdClass[] $allowList
     */
    public function setAllowList($allowList)
    {
        $this->allowList = $allowList;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @return int[]
     */
    public function getWhiteListIds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('White', 'Allow', __METHOD__))
        );

        return $this->getAllowListIds() + $this->whiteListIds;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param int[] $whiteListIds
     */
    public function setWhiteListIds($whiteListIds)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('White', 'Allow', __METHOD__))
        );

        $this->whiteListIds = $whiteListIds;
        $this->setAllowListIds($whiteListIds);
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @return \stdClass[]
     */
    public function getWhiteList()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('White', 'Allow', __METHOD__))
        );

        return $this->getAllowList() + $this->whiteList;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param \stdClass[] $whiteList
     */
    public function setWhiteList($whiteList)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('White', 'Allow', __METHOD__))
        );

        $this->whiteList = $whiteList;
        $this->setAllowList($whiteList);
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

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getErrorMessage(string $locale): string
    {
        return $this->errorMessages[$locale];
    }
}
