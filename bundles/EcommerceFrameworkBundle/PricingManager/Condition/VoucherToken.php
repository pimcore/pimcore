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
    protected $whiteListIds = [];

    /**
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

    public function checkVoucherCode($code)
    {
        if (in_array(VoucherServiceToken::getByCode($code)->getVoucherSeriesId(), $this->whiteListIds)) {
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

    /**
     * @param string $string
     *
     * @return ConditionInterface
     */
    public function fromJSON($string)
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
    public function getWhiteListIds()
    {
        return $this->whiteListIds;
    }

    /**
     * @param int[] $whiteListIds
     */
    public function setWhiteListIds($whiteListIds)
    {
        $this->whiteListIds = $whiteListIds;
    }

    /**
     * @return \stdClass[]
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }

    /**
     * @param \stdClass[] $whiteList
     */
    public function setWhiteList($whiteList)
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
