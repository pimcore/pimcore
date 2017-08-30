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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultService implements IVoucherService
{
    /**
     * @var int
     */
    protected $reservationMinutesThreshold;

    /**
     * @var int
     */
    protected $statisticsDaysThreshold;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        $this->reservationMinutesThreshold = $options['reservation_minutes_threshold'];
        $this->statisticsDaysThreshold = $options['statistics_days_threshold'];
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'reservation_minutes_threshold',
            'statistics_days_threshold'
        ]);

        $resolver->setDefaults([
            'reservation_minutes_threshold' => 5,
            'statistics_days_threshold'     => 30
        ]);

        $resolver->setAllowedTypes('reservation_minutes_threshold', 'int');
        $resolver->setAllowedTypes('statistics_days_threshold', 'int');
    }

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     *
     * @throws VoucherServiceException
     */
    public function checkToken($code, ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->checkToken($code, $cart);
        }
        throw new VoucherServiceException('No Token for code ' .$code . ' exists.', 3);
    }

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     */
    public function reserveToken($code, ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->reserveToken($code, $cart);
        }

        return false;
    }

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     */
    public function releaseToken($code, ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->releaseToken($code, $cart);
        }

        return false;
    }

    /**
     * @param string $code
     * @param ICart $cart
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function applyToken($code, ICart $cart, AbstractOrder $order)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            if ($orderToken = $tokenManager->applyToken($code, $cart, $order)) {
                $voucherTokens = $order->getVoucherTokens();
                $voucherTokens[] = $orderToken;
                $order->setVoucherTokens($voucherTokens);

                $this->releaseToken($code, $cart);

                return true;
            }
        }

        return false;
    }

    /**
     * Gets the correct token manager and calls removeAppliedTokenFromOrder(), which cleans up the
     * token usage and the ordered token object if necessary, removes the token object from the order.
     *
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherToken $tokenObject
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function removeAppliedTokenFromOrder(\Pimcore\Model\DataObject\OnlineShopVoucherToken $tokenObject, AbstractOrder $order)
    {
        if ($tokenManager = $tokenObject->getVoucherSeries()->getTokenManager()) {
            $tokenManager->removeAppliedTokenFromOrder($tokenObject, $order);

            $voucherTokens = $order->getVoucherTokens();

            $newVoucherTokens = [];
            foreach ($voucherTokens as $voucherToken) {
                if ($voucherToken->getId() != $tokenObject->getId()) {
                    $newVoucherTokens[] = $voucherToken;
                }
            }

            $order->setVoucherTokens($newVoucherTokens);

            return true;
        }

        return false;
    }

    /**
     * @param null $seriesId
     *
     * @return bool
     */
    public function cleanUpReservations($seriesId = null)
    {
        if (isset($seriesId)) {
            return Reservation::cleanUpReservations($this->reservationMinutesThreshold, $seriesId);
        } else {
            return Reservation::cleanUpReservations($this->reservationMinutesThreshold);
        }
    }

    /**
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherSeries $series
     *
     * @return bool
     */
    public function cleanUpVoucherSeries(\Pimcore\Model\DataObject\OnlineShopVoucherSeries $series)
    {
        return Token\Listing::cleanUpAllTokens($series->getId());
    }

    /**
     * @param null|string $seriesId
     *
     * @return bool
     */
    public function cleanUpStatistics($seriesId = null)
    {
        if (isset($seriesId)) {
            return Statistic::cleanUpStatistics($this->statisticsDaysThreshold, $seriesId);
        } else {
            return Statistic::cleanUpStatistics($this->statisticsDaysThreshold);
        }
    }

    /**
     * @param $code
     *
     * @return bool|TokenManager\ITokenManager
     */
    public function getTokenManager($code)
    {
        if ($token = Token::getByCode($code)) {
            if ($series = \Pimcore\Model\DataObject\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId())) {
                return $series->getTokenManager();
            }
        }

        return false;
    }
}
