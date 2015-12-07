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

namespace OnlineShop\Framework\PricingManager\Condition;

class VoucherToken implements \OnlineShop\Framework\PricingManager\ICondition
{
    /**
     * @var array
     */
    protected $whiteListIds = [];
    protected $whiteList = [];


    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {

        if (!($cart = $environment->getCart())) {
            return false;
        }

        $voucherTokenCodes = $cart->getVoucherTokenCodes();

        if (is_array($voucherTokenCodes)) {
            foreach ($voucherTokenCodes as $code) {
                if (in_array(\OnlineShop_Framework_VoucherService_Token::getByCode($code)->getVoucherSeriesId(), $this->whiteListIds)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = array(
            'type' => 'VoucherToken',
            'whiteList' => []
        );

        // add categories
        foreach ($this->getWhiteList() as $series) {
            /* @var \OnlineShop\Framework\Model\AbstractVoucherSeries $series */
            $json['whiteList'][] = array(
                $series->id,
                $series->path
            );
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $whiteListIds = array();
        $whiteList = array();

        foreach ($json->whiteList as $series) {
            $seriesId = $series->id;
            if ($seriesId) {
                $whiteListIds[] = $seriesId;
                $whiteList[] = $series;
            }
        }

        $this->setWhiteListIds($whiteListIds);
        $this->setWhiteList($whiteList);

        return $this;
    }

    /**
     * @param $id
     *
     * @return \Pimcore\Model\Object\Concrete|null
     */
    protected function loadSeries($id)
    {
        return \Pimcore\Model\Object\Concrete::getById($id);
    }

    /**
     * @return array
     */
    public function getWhiteListIds()
    {
        return $this->whiteListIds;
    }

    /**
     * @param array $whiteListIds
     */
    public function setWhiteListIds($whiteListIds)
    {
        $this->whiteListIds = $whiteListIds;
    }

    /**
     * @return array
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }

    /**
     * @param array $whiteList
     */
    public function setWhiteList($whiteList)
    {
        $this->whiteList = $whiteList;
    }


}